# Documentación de la API con Swagger

Toda la API de Wallet está documentada con **OpenAPI** y se puede consultar y probar desde el navegador con **Swagger UI**, sin necesidad de Postman.

> Este documento corresponde al ticket **WAL-022**. Para instalar y levantar el proyecto, ver el [README principal](../README.md).

## URL

```
http://localhost:8000/api/documentation
```

Requiere tener el servidor corriendo (`php artisan serve`) y la documentación generada.

## Generar o actualizar la documentación

```bash
php artisan l5-swagger:generate
```

- La documentación **no se versiona** (`/storage/api-docs` está en `.gitignore`): cada integrante la genera en su máquina.
- Hay que volver a generarla **cada vez que se modifica un atributo `#[OA\...]`**. Swagger UI no lee el código en vivo, sino el archivo generado.
- Si el navegador muestra la versión anterior, recargá con `Ctrl + F5`.

## Cómo usarla paso a paso

1. Abrí `POST /api/v1/auth/register` → **Try it out** → **Execute** para crear un usuario (o usá uno existente).
2. Abrí `POST /api/v1/auth/login` → **Try it out** → completá email y contraseña → **Execute**.
3. Copiá **solo** el valor de `access_token` de la respuesta.
4. Hacé clic en el botón **Authorize** (arriba a la derecha), pegá el token **sin escribir `Bearer`** delante y confirmá.
5. Listo: los endpoints con candado ya se pueden probar con **Try it out** → **Execute**.

El token dura **60 minutos**. Si empezás a recibir `401`, volvé a hacer login y cargá el token nuevo con **Authorize → Logout → Authorize**.

## Probar los endpoints de administración

Necesitan un usuario con rol `administrador`. El seeder crea uno:

```bash
php artisan db:seed --class=AdministradorSeeder
```

El email por defecto es `admin@wallet.local`. Las credenciales se pueden definir con `ADMIN_EMAIL` y `ADMIN_PASSWORD` en el `.env`; si no se definen, se usan las de `database/seeders/AdministradorSeeder.php`. Hacé login con ese usuario y cargá su token en **Authorize**.

## Cómo está organizada

| Sección | Endpoints |
|---|---|
| Autenticación | registro y login |
| Perfil | consultar, actualizar y dar de baja el perfil propio |
| Cuenta | consultar la cuenta y depositar |
| Movimientos | historial propio y transferencias |
| CBU de terceros | guardar, listar y quitar destinatarios frecuentes |
| Plazo fijo | simulación |
| Administración: usuarios | CRUD de usuarios |
| Administración: cuentas | CRUD de cuentas |
| Administración: movimientos | CRUD del historial de movimientos |

Las rutas de prueba `/api/v1/ping` y `/api/v1/admin/ping` no están documentadas a propósito.

## Formato de los errores

La mayoría de los errores (401, 403, 404 y 422) tienen el mismo formato:

```json
{
    "message": "Descripción del error",
    "status": 422,
    "error": {}
}
```

Algunos endpoints responden errores de negocio con un formato más simple, solo con `message` (por ejemplo, saldo insuficiente en una transferencia). Swagger indica en cada respuesta qué formato corresponde.

## Paginación y ordenamiento

| Listado | Parámetros | Orden por defecto |
|---|---|---|
| `GET /api/v1/movements` | `page` | fecha, descendente (fijo) |
| `GET /api/v1/admin/users` | `page`, `per_page`, `orden` | fecha de creación, descendente |
| `GET /api/v1/admin/accounts` | `page`, `per_page`, `orden`, `cbu` | nombre del titular, ascendente |
| `GET /api/v1/admin/movements` | `page`, `per_page`, `orden`, `cuenta_id`, `usuario_id` | fecha de creación, descendente |

`per_page` acepta de 1 a 100 (15 por defecto). `orden` acepta `asc` o `desc`.

## Cómo documentar un endpoint nuevo

1. En el controlador, agregá `use OpenApi\Attributes as OA;` si todavía no está.
2. Encima del método, agregá el atributo que corresponda (`#[OA\Get]`, `#[OA\Post]`, `#[OA\Put]`, `#[OA\Patch]` o `#[OA\Delete]`). **No modifiques el método.**
3. Los schemas reutilizables (`Account`, `ApiError`, paginación, etc.) están en `app/Http/Controllers/Controller.php`.
4. Si un método atiende dos rutas (por ejemplo `PUT` y `PATCH`), cada atributo necesita un `operationId` distinto.
5. Regenerá con `php artisan l5-swagger:generate` y probalo en Swagger UI.

La documentación tiene que describir lo que la API **hace realmente**: antes de documentar, revisá la ruta, la validación y la respuesta JSON real.

## ⚠️ Advertencias sinceras

Al documentar la API se relevaron estas diferencias entre lo que pide el backlog y lo que hace el código. **Swagger documenta el comportamiento real**, no el esperado. Quedan registradas para decidir si se corrigen.

### 1. El borrado de movimientos es definitivo

`DELETE /api/v1/admin/movements/{movimiento}`

El modelo `Movimiento` no usa `SoftDeletes`, así que el borrado es físico y no se puede deshacer. En cambio, usuarios y cuentas tienen baja lógica. Además, editar o eliminar un movimiento **no recalcula el saldo** de la cuenta (es el comportamiento pedido por WAL-018).

### 2. Dar de baja una cuenta responde 204 con un mensaje que nunca llega

`DELETE /api/v1/admin/accounts/{cuenta}`

El código devuelve `response()->json(['message' => ...], 204)`. El código 204 significa "sin contenido" y HTTP no permite cuerpo, así que Laravel descarta el mensaje y el cliente recibe una respuesta vacía.

**Posible fix:** responder `200` con el mensaje, o `response()->noContent()` sin mensaje.

### 3. Plazo fijo: dos diferencias con WAL-014

`POST /api/v1/investments/fixed-term/simulate`

- La TNA (30%) está fija como constante en `fixedTermServices`, no en la configuración como pedía el ticket.
  **Posible fix:** moverla a un archivo de `config/` leído desde una variable de entorno.
- Solo acepta el plazo en días (`plazo`). No existe la opción de indicar una fecha futura.

### 4. El historial propio no acepta `per_page` ni orden

`GET /api/v1/movements`

Pagina siempre de a 15 y ordena siempre del más reciente al más antiguo. WAL-009 pedía un máximo de 100 por página y orden ascendente o descendente.

**Posible fix:** validar `per_page` y `orden` como lo hacen los listados de administración.

### 5. Se eliminó un service huérfano que bloqueaba la generación

`app/Services/Admin/CreateAdminAccountService.php` declaraba la clase `StoreAdminAccountService` con el namespace `App\Services\Auth`: no coincidía ni con el nombre del archivo ni con la carpeta, por lo que Laravel no podía cargarla y la generación de Swagger fallaba. Ninguna parte del código la usaba. Se eliminó en un commit separado y la suite de tests pasó completa antes y después.

### Otras observaciones menores

- `POST /api/v1/transfers` responde el 422 en dos formatos: el general (errores de validación) y el simple con solo `message` (saldo insuficiente o misma cuenta). Quien consuma la API tiene que contemplar ambos.
- Cuando un recurso de administración no existe, el mensaje del 404 es el de Laravel por defecto (por ejemplo `No query results for model [App\Models\User] 9`) y expone el nombre interno de la clase.
