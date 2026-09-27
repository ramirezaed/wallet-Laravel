# Wallet Squad1 - API Billetera Virtual

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

API RESTful para la gestión de una billetera virtual, desarrollada con Laravel y PHP. Permite a los usuarios realizar transacciones, depósitos, inversiones simuladas (plazo fijo), y administrar sus cuentas. Incluye un sistema de roles (Usuario/Administrador) con permisos diferenciados.

Este repositorio contiene la base del proyecto. Los modelos, controladores y la lógica de negocio se desarrollarán a partir de este punto de partida.

## 📋 Requisitos Previos

Asegúrate de tener instalado lo siguiente en tu entorno de desarrollo:

- **PHP**: ^8.2
- **Composer**: Última versión estable
- **Base de datos**: MySQL (o MariaDB) / PostgreSQL / SQLite
- **Node.js y NPM**: (Opcional, para compilar assets, aunque no es crítico para la API)
- **Git**

## 🚀 Instalación y Configuración

Sigue estos pasos para poner el proyecto en funcionamiento en tu máquina local.

### 1. Clonar el repositorio

```bash
git clone https://github.com/hectordsol/wallet-squad1-php.git
cd wallet-squad1-php
```

### 2. Instalar dependencias de PHP

Instala las dependencias del proyecto usando Composer. Esto incluye el framework Laravel y los paquetes necesarios.

```bash
composer install
```

### 3. Configurar variables de entorno (.env)

```bash
php artisan key:generate
```

#### Configuración de la base de datos:

Abre el archivo `.env` y ajusta los parámetros de conexión a tu base de datos. Por ejemplo, para MySQL:

```text
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallet_principal
DB_USERNAME=admin o tu_usuario
DB_PASSWORD=tu_contraseña

```

### 4. Ejecutar migraciones y seeders

Las migraciones cuando sean creadas armarán la estructura de tablas en tu base de datos. Los seeders (si están definidos) poblarán la base con datos de prueba, como un usuario administrador por defecto.

```bash
php artisan migrate --seed
```

### 5. Iniciar el servidor de desarrollo

Para levantar la API y comenzar a probarla, ejecuta el siguiente comando:

```bash
php artisan serve
```

La API estará disponible en `http://127.0.0.1:8000.`

## 📦 Dependencias Principales

- Laravel Framework: v11.x. Proporciona la estructura base, el ORM Eloquent, el sistema de rutas y la capa de seguridad.

- JWT Auth: ^2.0. Maneja la autenticación basada en tokens JWT para las peticiones a la API.

## 📦 Base de datos

La base de datos por el momento solo tiene el modelo de usuarios que viene con Laravel.

## 🔐 Registro de Usuarios

La API incluye un endpoint para el registro de nuevos usuarios en la billetera virtual.

### Endpoint: POST /api/v1/auth/register

Cuerpo de la Petición (JSON):

```json
{
    "nombre": "ale",
    "email": "usuario@usuario.com",
    "password": "1234",
    "password_confirmation": "1234",
    "edad": 18
}
```

Campos:

| Campo                   | Tipo   | Obligatorio | Descripción                                                 |
| ----------------------- | ------ | ----------- | ----------------------------------------------------------- |
| `nombre`                | string | Si          | Nombre del usuario                                          |
| `email`                 | string | Si          | Identifica el usuario como único                            |
| `password`              | string | Si          | contraseña Obligatorio                                      |
| `password_confirmation` | string | Si          | Confirmación de la contraseña (debe coincidir con password) |
| `edad`                  | string | Si          | Edad del usuario obligatorio mayor a 18                     |
| `rol`                   | string | No          | Por defecto se asigna "usuario"                             |

**Respuesta exitosa:** ![201 Created](https://img.shields.io/badge/201-Created-green)

```json
{
    "id": 1,
    "nombre": "ale",
    "email": "usuario@usuario.com",
    "edad": 18,
    "rol": "usuario",
    "created_at": "2026-01-01T00:00:00.000000Z"
}
```

Respuestas de Error (422 Unprocessable Entity):
El endpoint retorna un error 422 en los siguientes casos:

Campos faltantes: Cuando algún campo obligatorio no es enviado.

Email duplicado: Si el correo electrónico ya está registrado en el sistema.

Validación de contraseña: Cuando password y password_confirmation no coinciden.

Ejemplo de Respuesta de Error:

```json
{
    "message": "El email ya ha sido registrado.",
    "errors": {
        "email": ["El email ya ha sido registrado."]
    }
}
```

**Respuesta no exitosa:** ![422 Unprocessable content](https://img.shields.io/badge/422-Unprocessable_content-red)

Notas:

Por defecto, todos los usuarios nuevos se registran con el rol "usuario".

El rol "administrador" solo puede ser asignado manualmente desde la base de datos o mediante un proceso específico de administración.

```bash
php artisan test
```

## WAL-005 — Consultar el perfil propio

Permite obtener los datos personales del usuario autenticado.

### Endpoint

```http
GET /api/v1/profile
```

### Autenticación

Requiere un JWT válido mediante Bearer Token.

```http
Authorization: Bearer {token}
```

El usuario se obtiene directamente desde el token JWT, por lo que no es necesario enviar `user_id`. De esta forma, cada usuario solamente puede consultar su propio perfil.

### Respuesta exitosa

**HTTP 200 OK**

```json
{
    "id": 2,
    "name": "Julio",
    "email": "julio2@test.com"
}
```

**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green).

La respuesta no incluye la contraseña ni otros datos privados del usuario.

### Sin token o token inválido

**Respuesta no exitosa:** ![401 Unauthorized](https://img.shields.io/badge/401-Unauthorized-red)

La API rechaza el acceso cuando no se proporciona un JWT válido.

---

## WAL-006 — Consultar la cuenta y el saldo propios

Permite obtener el CBU y saldo de la cuenta perteneciente al usuario autenticado.

### Endpoint

```http
GET /api/v1/account
```

### Autenticación

Requiere un JWT válido mediante Bearer Token.

```http
Authorization: Bearer {token}
```

La cuenta se obtiene a través del usuario identificado por el JWT. No es necesario enviar `user_id` ni `account_id`, evitando que un usuario pueda seleccionar o consultar la cuenta de otro usuario.

### Respuesta exitosa

**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green).

```json
{
    "cbu": "0000009341854172124306",
    "balance": "0.00"
}
```

El campo `balance` se devuelve siempre con dos decimales.

### Cuenta inexistente

**Respuesta no exitosa:** ![404 Not Found](https://img.shields.io/badge/404-Not_Found-red)

```json
{
    "message": "Cuenta no encontrada"
}
```

### Sin token o token inválido

**HTTP 401 Unauthorized**
**Respuesta no exitosa:** ![401 Unauthorized](https://img.shields.io/badge/401-Unauthorized-red)

La API rechaza el acceso cuando no se proporciona un JWT válido.

---

## Ejemplo de uso

Primero se inicia sesión para obtener el JWT:

```http
POST /api/v1/auth/login
```

```json
{
    "email": "julio2@test.com",
    "password": "12345678"
}
```

Una vez obtenido el `access_token`, se utiliza como Bearer Token para consultar los endpoints privados:

```http
GET /api/v1/profile
Authorization: Bearer {access_token}
```

```http
GET /api/v1/account
Authorization: Bearer {access_token}
```

## Implementación

Para estos endpoints no se utilizan Form Requests ni DTOs porque las operaciones son consultas `GET` que no reciben datos de entrada.

Se utilizan API Resources para controlar los campos expuestos por la API:

- `ProfileResource`: transforma los datos del usuario y expone `id`, `name` y `email`.
- `AccountResource`: transforma los datos de la cuenta y expone `cbu` y `balance`.

Ambos endpoints se encuentran protegidos por el middleware:

```php
auth:api
```

El usuario autenticado se obtiene mediante el guard JWT:

```php
Auth::guard('api')->user();
```

Para consultar la cuenta se utiliza la relación entre el usuario autenticado y su cuenta, sin aceptar identificadores enviados por el cliente.

### Tests WAL-005 y WAL-006

Los endpoints de perfil y cuenta cuentan con tests de integración.

```bash
php artisan test
```

## WAL-007 — Permite hacer depósito

Como usuario autenticado, quiere depositar dinero en mi cuenta.

Primero inicia sesión para obtener el JWT:

### Primero iniciar sesion

```http
POST /api/v1/auth/login
```

```json
{
    "email": "julio2@test.com",
    "password": "12345678"
}
```

Una vez obtenido el `access_token`, se utiliza como Bearer Token para realizar un depósito:

### Endpoint para depositar

```http
POST /api/v1/deposits
Authorization: Bearer {access_token}
```

Cuerpo de la Petición (JSON):

```json
{
    "amount": 1000
}
```

| Campo     | Tipo    | Obligatorio | Descripción                                               |
| --------- | ------- | ----------- | --------------------------------------------------------- |
| `ammount` | decimal | Si          | Monto a depositar en cuenta propia, no negativo mayor a 0 |

**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green)

```json
{
    "cbu": "0000009517611939773286",
    "saldo": "1101.00"
}
```

- Opera sólo sobre la cuenta autenticada y aumenta exactamente su saldo.
- Crea exactamente un movimiento `"deposito"` con la misma cuenta y monto en la tabal Movimientos.
- Devuelve el nuevo saldo con dos decimales.

En caso de envío erroneo o cero devuelve 422:

```json
{
    "amount": -4
}
```

**Respuesta no exitosa:** ![422 Unprocessable content](https://img.shields.io/badge/422-Unprocessable_content-red)

```json
{
    "message": "El monto debe ser al menos 0.01.",
    "status": 422,
    "error": {}
}
```

```http
PUT /api/v1/profile
Authorization: Bearer {access_token}
```

```json
{
    "message": "No autenticado",
    "status": 401,
    "error": {}
}
```

Una vez obtenido el `access_token`, se utiliza como Bearer Token para realizar un depósito:

### 🧪 Pruebas (Tests)

Los endpoints `/api/v1/deposits` cuenta cuentan con tests de integración de usuario autenticado y casos de error en el envío del monto.

## WAL-008 — Transferir dinero entre cuentas

Permite enviar dinero desde la cuenta autenticada hacia otra cuenta existente, registrada por su CBU.

### Endpoint

```http
POST /api/v1/transfers
```

### Autenticación

Requiere un JWT válido mediante Bearer Token.

```http
Authorization: Bearer {token}
```

### Cuerpo de la petición

```json
{
    "destination_cbu": "0000009517611939773286",
    "amount": 250.5
}
```

| Campo             | Tipo    | Obligatorio | Descripción                                                                 |
| ----------------- | ------- | ----------- | --------------------------------------------------------------------------- |
| `destination_cbu` | string  | Sí          | CBU de la cuenta destinataria.                                              |
| `amount`          | numeric | Sí          | Monto a transferir. Debe ser mayor a cero y no superar el saldo disponible. |

### Comportamiento

- La cuenta de origen es la cuenta del usuario autenticado.
- La cuenta de destino se busca por su `cbu`.
- Si la cuenta de origen o destino no existe, responde con `404`.
- Si la cuenta de origen y destino son la misma, responde con `422`.
- Si el monto supera el saldo disponible, responde con `422`.
- La operación se ejecuta dentro de una transacción para asegurar consistencia.
- Se registran dos movimientos:
    - `transferencia_salida` en la cuenta origen.
    - `transferencia_entrada` en la cuenta destino.

### Respuesta exitosa

**HTTP 200 OK**

```json
{
    "message": "Transferencia realizada con éxito"
}
```

### Cuenta inexistente

**HTTP 404 Not Found**

```json
{
    "message": "la cuenta de origen o destino no existe"
}
```

### Mismo CBU de origen y destino

**HTTP 422 Unprocessable Entity**

```json
{
    "message": "No se puede transferir a la misma cuenta"
}
```

### Saldo insuficiente

**HTTP 422 Unprocessable Entity**

```json
{
    "message": "Saldo insuficiente para realizar la transferencia"
}
```

---

## Ejemplo de uso

Primero se inicia sesión para obtener el JWT:

```http
POST /api/v1/auth/login
```

```json
{
    "email": "julio2@test.com",
    "password": "12345678"
}
```

Una vez obtenido el `access_token`, se utiliza como Bearer Token para consultar los endpoints privados:

```http
GET /api/v1/profile
Authorization: Bearer {access_token}
```

```http
GET /api/v1/account
Authorization: Bearer {access_token}
```

```http
POST /api/v1/transfers
Authorization: Bearer {access_token}
```

```json
{
    "destination_cbu": "0000009517611939773286",
    "amount": 250.5
}
```

## Implementación

Para estos endpoints no se utilizan Form Requests ni DTOs porque las operaciones son consultas `GET` que no reciben datos de entrada.

Se utilizan API Resources para controlar los campos expuestos por la API:

- `ProfileResource`: transforma los datos del usuario y expone `id`, `name` y `email`.
- `AccountResource`: transforma los datos de la cuenta y expone `cbu` y `balance`.

Ambos endpoints se encuentran protegidos por el middleware:

```php
auth:api
```

El usuario autenticado se obtiene mediante el guard JWT:

```php
Auth::guard('api')->user();
```

Para consultar la cuenta se utiliza la relación entre el usuario autenticado y su cuenta, sin aceptar identificadores enviados por el cliente.



## WAL-012 — Actualizar o eliminar el perfil propio

Tanto para actualizar con el método PUT o eliminar con el método DELETE del perfil debe estar autenticado. En caso de intentar alguna de estas acciones sin autenticar devuelve sin no autorizado:

```json
{
    "message": "No autenticado",
    "status": 401,
    "error": {}
}
```

### 🔄 Actualizar Perfil de Usuario

Actualiza la información del perfil del usuario autenticado. Todos los campos son opcionales; solo se actualizarán aquellos que envíes en la petición.

- Método: PUT

- URL: `/api/v1/auth/profile`

- Autenticación: Requerida (Bearer Token)

Modificar un archivo php.ini de Laravel Herd y reiniciar HERD:

```text
upload_tmp_dir = "C:\Users\usuario\AppData\Local\Temp"

; Maximum allowed size for uploaded files.
; https://php.net/upload-max-filesize
upload_max_filesize = 8M

; Maximum number of files that can be uploaded via a single request
max_file_uploads = 20

post_max_size=10M
```

Ejecutar una sola vez:

```bash
php artisan storage: link
```

Laravel crea un enlace entre:

```text
public/storage
```

y:

```text
storage/app/public
```

De esta manera, los archivos almacenados en el disco `public` pueden ser accedidos desde la aplicación.

### 📥 Envío de datos (Body)

Para enviar información en el Body de la petición, debes seleccionar la opción `form-data` en tu cliente HTTP (Postman, Insomnia, Thunder Client, etc.) y cargar únicamente los campos que deseas actualizar. Todos los campos son opcionales.

### 📋 Campos disponibles

| Campo                   | Tipo   | Obligatorio | Descripción                                                                               |
| ----------------------- | ------ | ----------- | ----------------------------------------------------------------------------------------- |
| `nombre`                | string | No          | Nombre del usuario                                                                        |
| `email`                 | string | No          | Identifica el usuario como único                                                          |
| `password`              | string | No          | contraseña nueva del usuario                                                              |
| `password_confirmation` | string | Condicional | Confirmación de la contraseña (debe coincidir con password)                               |
| `edad`                  | string | No          | Edad del usuario debe ser mayor a 18 y menor a 120                                        |
| `imagen`                | File   | No          | Imagen de perfil. Debes seleccionar un archivo (tipo File) desde el selector de form-data |

⚠️ Importante:

- Si envías el campo imagen, en form-data se debe cambiar el tipo de campo de Text a File y seleccionar el archivo desde tu equipo.

**Respuesta no exitosa:** ![422 Unprocessable content](https://img.shields.io/badge/422-Unprocessable_content-red)

📋 Tabla de errores por campo

| Campo      | Regla     | Mensaje                                                      |
| ---------- | --------- | ------------------------------------------------------------ |
| `nombre`   | max       | El nombre del usuario no puede tener más de 255 caracteres.  |
| `email`    | email     | El correo electrónico debe tener un formato válido           |
| `email`    | unique    | El correo electrónico ya está en uso                         |
| `password` | string    | La contraseña debe tener al menos 8 caracteres               |
| `password` | confirmed | Debe ingresar nuevamente la misma contraseña                 |
| `edad`     | integer   | La edad debe ser un número entero                            |
| `edad`     | between   | La edad debe estar entre 18 y 120 años                       |
| `imagen`   | file      | file La imagen debe ser un archivo válido                    |
| `imagen`   | image     | El archivo debe ser una imagen válida                        |
| `imagen`   | uploaded  | No se pudo cargar la imagen. Verifica que no supere los 2 MB |
| `imagen`   | mimes     | La imagen debe estar en formato JPG, JPEG, PNG o WebP.       |


### 🗑️ Eliminar Cuenta del Usuario Autenticado

Elimina la cuenta del usuario autenticado mediante un borrado lógico (soft delete). El registro no se elimina físicamente de la base de datos;

- Método: DELETE

- URL: `/api/v1/profile`

- Autenticación: Requerida (Bearer Token)

- Body: Ninguno

HTTP 204 No Content

### 🔒 Comportamiento de la eliminación
La eliminación del usuario no borra físicamente el registro ni sus datos relacionados. Se aplica un soft delete, por lo que:

| Entidad afectada | Comportamiento |
|---|---|
| Usuario | Se marca como eliminado (deleted_at con la fecha/hora). El registro permanece en la tabla users |
| Movimientos | Se conservan en la base de datos. Las claves foráneas permanecen intactas para preservar el historial |
| CBU guardados | Se conservan en la base de datos. Las claves foráneas permanecen intactas |
| Sesión / Token | Se revoca el token de acceso actual. El usuario ya no puede autenticarse |



### 🚫 Intento de ingreso tras la eliminación

Si el usuario intenta iniciar sesión luego de haber eliminado su cuenta:

- ❌ No se le permite el acceso.

- ❌ La respuesta del login devolverá un error de credenciales inválidas (HTTP 401 Unauthorized):

```json
{
  "message": "Credenciales inválidas."
}
```

### ♻️ Registro nuevamente tras la eliminación

Si el usuario intenta registrarse nuevamente con el mismo correo electrónico:

- Se recupera la información previamente asociada a su cuenta.

- Los movimientos y CBU guardados previamente asociados vuelven a estar disponibles automáticamente al reactivarse la cuenta.


## WAL-014 Simular plazo fijo

Permite simular una inversión a plazo fijo utilizando interés simple. El usuario autenticado envía el monto a invertir y el plazo en días, y la API devuelve el interés ganado, el total a recibir y las fechas de creación y finalización de la inversión.

Esta operación es solo una simulación: no persiste datos, no modifica el saldo de la cuenta y no genera movimientos.

## Endpoint

`POST /api/v1/investments/fixed-term/simulate`

## cuerpo de la peticion

```json
{
"monto": 100000,
"plazo": 30
}
```

## Reglas de negocio

    Se utiliza interés simple.
    TNA (Tasa Nominal Anual): 30%.
    Base de cálculo: 365 días.
    Plazo permitido: entre 30 y 365 días.

## Respuesta exitosa

    HTTP 200 OK
```json
"data": {
    "Fecha de inicio": "2026-09-16T12:06:27.000000Z",
    "Fecha de fin": "2026-10-16T12:06:27.000000Z"
    "Monto Invertido": 100000,
    "Interes ganado": 2465.75,
    "Total": 102465.75,
}
```


## WAL-017 — Administrar Cuentas

Permite a un usuario con rol `administrador` gestionar cuentas de usuarios.

Todas las rutas requieren autenticación JWT y rol de administrador.

### Autenticación

```http
Authorization: Bearer {access_token}
```

### Listar cuentas administrativos

Un usuario sin token devuelve:

```json
{
    "message": "No autenticado",
    "status": 401,
    "error": {}
}
```
**Respuesta no exitosa:** ![401 Unauthorized](https://img.shields.io/badge/401-Unauthorized-red)

En caso de iniciar sesión como usuario de cuenta:

```json
{
    "message": "No autorizado. Se requiere rol de administrador.",
    "status": 403,
    "error": {}
}
```
**Respuesta no exitosa:** ![403 Forbidden](https://img.shields.io/badge/403-Forbidden-red)


### Endpoint

```json
GET /api/v1/admin/accounts` 
```
Devuelve listado completo paginado. El listado utiliza paginación nativa de Laravel y admite búsqueda por CBU. La respuesta por defecto envía paginado de 15 cuentas por página, orden ascendente en base a nombre de usuario de cuenta.
Para enviar una petición personalizada solicitar por body:

### cuerpo de la peticion (opcional):

```json
{
    "orden" : "desc",
    "per_page" : 10
}
```

### Campos:

| Campo                   | Tipo   | Obligatorio | Descripción                                                                               |
| ----------------------- | ------ | ----------- | ----------------------------------------------------------------------------------------- |
| `orden`	| string	| No	| "asc" o "desc". Si no se envía orden es "asc" |
| `per_page`	| integer	| No	| Número entero indicando cuentas por página. Si no se envía son 15 |

### Ejemplo de respuesta

```json
{
    "data": [
        {
            "id": 8,
            "usuario_id": 8,
            "cbu": "4193711975380485558941",
            "saldo": "0.00",
            "tipo": "ahorro",
            "moneda": "ARS",
            "nombre_usuario": "Verónica Garica",
            "created_at": "2026-09-20T22:34:38.000000Z"
        },
        {
            "id": 5,
            "usuario_id": 5,
            "cbu": "6617429190875630447053",
            "saldo": "0.00",
            "tipo": "ahorro",
            "moneda": "ARS",
            "nombre_usuario": "Unai Carrión",
            "created_at": "2026-09-20T22:34:38.000000Z"
        },
..
        {
            "id": 21,
            "usuario_id": 21,
            "cbu": "2087882938394801181022",
            "saldo": "0.00",
            "tipo": "ahorro",
            "moneda": "ARS",
            "nombre_usuario": "Ing. Iker Vela",
            "created_at": "2026-09-20T22:34:38.000000Z"
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8000/api/v1/admin/accounts?page=1",
        "last": "http://127.0.0.1:8000/api/v1/admin/accounts?page=3",
        "prev": null,
        "next": "http://127.0.0.1:8000/api/v1/admin/accounts?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 3,
        "links": [
            {
                "url": null,
                "label": "pagination.previous",
                "page": null,
                "active": false
            },
            {
                "url": "http://127.0.0.1:8000/api/v1/admin/accounts?page=1",
                "label": "1",
                "page": 1,
                "active": true
            },
            {
                "url": "http://127.0.0.1:8000/api/v1/admin/accounts?page=2",
                "label": "2",
                "page": 2,
                "active": false
            },
            {
                "url": "http://127.0.0.1:8000/api/v1/admin/accounts?page=3",
                "label": "3",
                "page": 3,
                "active": false
            },
            {
                "url": "http://127.0.0.1:8000/api/v1/admin/accounts?page=2",
                "label": "pagination.next",
                "page": 2,
                "active": false
            }
        ],
        "path": "http://127.0.0.1:8000/api/v1/admin/accounts",
        "per_page": 10,
        "to": 10,
        "total": 23
    }
}
```
**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green).

### Ejemplo de respuesta no exitosa
Si en la petición el cuerpo contiene lo siguiente:
```json
{
    "orden" : "desc",
    "per_page" : 102
}
```
La respuesta sería:

```json
{
    "message": "El valor del parámetro \"per_page\" no puede ser mayor a 100.",
    "status": 422,
    "error": {}
}
```


### consulta por Query de CBU

`GET /api/v1/admin/accounts?cbu=8236816523152794428993`: devuelve una cuenta si el número de cbu existe en la tabla de cuentas.

```json
{
    "id": 4,
    "usuario_id": 4,
    "cbu": "8236816523152794428993",
    "saldo": "0.00",
    "tipo": "ahorro",
    "moneda": "ARS",
    "nombre_usuario": "África Gaytán",
    "created_at": "2026-09-20T22:34:38.000000Z"
}
```
**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green).



### consulta por Parametro Cuenta desde Administrador

Endpoint
```http
GET /api/v1/admin/accounts/{cuenta}` 
```


`GET /api/v1/admin/accounts/4`
 devuelve una cuenta si el id pasado por parámetro existe en la tabla de cuentas

```json
{
    "id": 4,
    "usuario_id": 4,
    "cbu": "8236816523152794428993",
    "saldo": "0.00",
    "tipo": "ahorro",
    "moneda": "ARS",
    "nombre_usuario": "África Gaytán",
    "created_at": "2026-09-20T22:34:38.000000Z"
}
```

**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green).

```json
{
    "message": "No query results for model [App\\Models\\Cuenta] 222",
    "status": 404,
    "error": {}
}
```
**Respuesta no exitosa:** ![404 Not Found](https://img.shields.io/badge/404-Not_Found-red).


### Crear cuenta desde administrador

Un administrador puede crear una cuenta a un usuario que no tenga cuenta creada:
Endpoint:
```http
POST /api/v1/admin/accounts`
```
Cuerpo de la Petición (JSON):

```json
{
    "usuario_id" : "23",
    "tipo" : "ahorro",
    "moneda" : "ARS"
}
```
Respuesta:

```json
{
    "id": 25,
    "usuario_id": 23,
    "cbu": "0000009765243354564986",
    "saldo": "0.00",
    "tipo": "ahorro",
    "moneda": "ARS",
    "nombre_usuario": "Roberto Carrasquillo",
    "created_at": "2026-09-21T17:39:43.000000Z"
}
```

**Respuesta exitosa:** ![201 Created](https://img.shields.io/badge/201-Created-green)



### Actualizar Cuenta desde Administrador
Un administrador puede actualizar una cuenta a un usuario. Solo se puede actualizar de manera opcional el tipo de cuenta (ahorro o corriente). También se puede cambiar el tipo de moneda, si es USD para dólar o ARS, para pesos argentinos.
Endpoint:
```http
PUT o PATCH /api/v1/admin/accounts/{cuenta}`
```
Ejemplo donde la cuenta con id 25 era caja de ahorro, pasa a ser de cuenta corriente:
```http
PUT /api/v1/admin/accounts/25
```

Cuerpo de la Petición (JSON):
```json
{
    "tipo" : "corriente",
    "moneda" : "ARS"
}
```
Respuesta:
```json
{
    "id": 25,
    "usuario_id": 23,
    "cbu": "0000009765243354564986",
    "saldo": "0.00",
    "tipo": "corriente",
    "moneda": "ARS",
    "nombre_usuario": "Roberto Carrasquillo",
    "created_at": "2026-09-21T17:39:43.000000Z"
}
```
**Respuesta exitosa:** ![200 OK](https://img.shields.io/badge/200-OK-green).

### Borrar Cuenta desde Administrador
Un administrador puede borrar una cuenta de un usuario. El borrado es con SoftDelete. Lo que permite restaurar la cuenta desde Crear Cuenta con el Administrador
Endpoint:
```http
DELETE /api/v1/admin/accounts/{cuenta}`
```
```http
PUT /api/v1/admin/accounts/25
```

**Respuesta exitosa:** ![204 No Content](https://img.shields.io/badge/204-No_Content-green).



## WAL-018 — Administrar transacciones o movimientos

Permite a un usuario con rol `administrador` gestionar el historial de movimientos de las cuentas.

Todas las rutas requieren autenticación JWT y rol de administrador.

### Autenticación

```http
Authorization: Bearer {access_token}
```

Un usuario sin token recibe:

HTTP 401 Unauthorized

Un usuario autenticado con rol distinto de administrador recibe:

HTTP 403 Forbidden

```json
{
    "message": "No autorizado. Se requiere rol de administrador.",
    "status": 403,
    "error": {}
}
```

### Listar movimientos administrativos
GET /api/v1/admin/movements

El listado utiliza paginación nativa de Laravel y admite filtros y ordenamiento.

Parámetros disponibles:

Parámetro	Tipo	Descripción
cuenta_id	integer	Filtra los movimientos por cuenta.
usuario_id	integer	Filtra los movimientos por usuario propietario de la cuenta.
orden	string	Orden por fecha. Valores permitidos: asc o desc.
per_page	integer	Cantidad de elementos por página. Máximo: 100.

Ejemplos:

GET /api/v1/admin/movements?cuenta_id=1
GET /api/v1/admin/movements?usuario_id=1
GET /api/v1/admin/movements?orden=asc
GET /api/v1/admin/movements?per_page=5

Ejemplo de elemento devuelto:

```json
{
    "id": 8,
    "tipo": "deposito",
    "monto": "250.00",
    "cbu_contraparte": null,
    "fecha": "2026-09-16T12:06:27.000000Z",
    "cuenta": {
        "id": 1,
        "cbu": "0000009539205127166181",
        "tipo": "ahorro",
        "moneda": "ARS",
        "usuario_id": 1
    }
}
```

Si se envía un valor inválido, por ejemplo:

`GET /api/v1/admin/movements?per_page=101`

la API responde:

HTTP 422 Unprocessable Entity

### Crear un movimiento
POST /api/v1/admin/movements

Cuerpo de ejemplo:
```json
{
    "cuenta_id": 1,
    "tipo": "deposito",
    "monto": 250,
    "cbu_contraparte": null
}
```

Campos:

| Campo                   | Tipo   | Obligatorio | Descripción                                                                               |
| ----------------------- | ------ | ----------- | ----------------------------------------------------------------------------------------- |
| `cuenta_id`	| integer	| Sí	| ID de una cuenta existente |
| `tipo`	| string	| Sí	| deposito, transferencia_salida o transferencia_entrada |
| `monto`	| numeric	| Sí	| Debe ser mayor que cero |
| `cbu_contraparte`	| string/null	| No	| CBU asociado al movimiento cuando corresponde |

HTTP 201 Created

```json
{
    "id": 9,
    "tipo": "deposito",
    "monto": "250.00",
    "cbu_contraparte": null,
    "fecha": "2026-09-16T12:15:42.000000Z",
    "cuenta": {
        "id": 1,
        "cbu": "0000009539205127166181",
        "tipo": "ahorro",
        "moneda": "ARS",
        "usuario_id": 1
    }
}
```

### Consultar un movimiento
GET /api/v1/admin/movements/{movimiento}

Devuelve el movimiento solicitado junto con los datos de su cuenta.

### Actualizar un movimiento

Se admite PUT o PATCH.

PUT /api/v1/admin/movements/{movimiento}

Ejemplo:

```json
{
    "monto": 5000
}
```

HTTP 200 OK

```json
{
    "id": 9,
    "tipo": "deposito",
    "monto": "5000.00",
    "cbu_contraparte": null,
    "fecha": "2026-09-16T12:15:42.000000Z",
    "cuenta": {
        "id": 1,
        "cbu": "0000009539205127166181",
        "tipo": "ahorro",
        "moneda": "ARS",
        "usuario_id": 1
    }
}
```

Importante: modificar administrativamente un movimiento cambia únicamente el historial. No recalcula ni modifica automáticamente el saldo de la cuenta.

### Eliminar un movimiento

DELETE /api/v1/admin/movements/{movimiento}

HTTP 200 OK

```json
{
    "message": "Movimiento eliminado correctamente"
}
```


Eliminar un movimiento del historial administrativo no modifica automáticamente el saldo de la cuenta.

### Validaciones

Al crear o actualizar un movimiento se validan:

- existencia de la cuenta;
- tipo de movimiento permitido;
- monto numérico mayor que cero;
- CBU contraparte, cuando se informa.

Las validaciones incorrectas responden:

HTTP 422 Unprocessable Entity

### Pruebas WAL-018

Se agregaron tests de integración para comprobar:

- acceso de administrador;
- rechazo de usuario común con 403;
- rechazo sin token con 401;
- listado administrativo de movimientos;
- creación de movimientos;
- consulta individual;
- actualización;
- eliminación;
- filtros por cuenta;
- paginación;
- rechazo de per_page mayor a 100;
- actualización de movimientos sin modificar el saldo;
- eliminación de movimientos sin modificar el saldo.

Para ejecutar la suite:

```bash
php artisan test
```

## WAL-020 — Unificar errores HTTP y seguridad básica

Se unificó el manejo de errores HTTP de la API y se agregaron medidas básicas de seguridad para mantener respuestas JSON consistentes y evitar la exposición de información sensible.

### Respuestas de error de la API

Todas las solicitudes realizadas bajo `/api/v1` devuelven errores en formato JSON, incluso cuando el cliente no envía el header:

```http
Accept: application/json
```

La API utiliza de forma consistente los siguientes códigos HTTP:

| Código | Descripción |
|---|---|
| `401 Unauthorized` | El usuario no está autenticado o el token es inválido |
| `403 Forbidden` | El usuario está autenticado pero no tiene permisos para realizar la acción |
| `404 Not Found` | El recurso o la ruta solicitada no existe |
| `422 Unprocessable Entity` | Los datos enviados no superan las validaciones |
| `429 Too Many Requests` | Se superó el límite de intentos fallidos de inicio de sesión |
| `500 Internal Server Error` | Se produjo un error interno no controlado |

### Error 401 — No autenticado

Las rutas protegidas requieren un JWT válido.

Ejemplo:

```json
{
    "message": "No autenticado",
    "status": 401,
    "error": {}
}
```

Las peticiones realizadas a `/api/v1` no son redirigidas a una ruta web de login.

### Error 403 — Sin permisos

Un usuario autenticado que intenta acceder a una ruta administrativa sin poseer el rol `administrador` recibe:

```json
{
    "message": "No autorizado. Se requiere rol de administrador.",
    "status": 403,
    "error": {}
}
```

### Error 404 — Recurso inexistente

Cuando una ruta o recurso solicitado no existe, la API responde en formato JSON con estado:

```text
HTTP 404 Not Found
```

### Error 422 — Validación

Los errores producidos por los Form Requests se devuelven en formato JSON utilizando:

```text
HTTP 422 Unprocessable Entity
```

### Protección de errores internos

Los errores internos del servidor no exponen al cliente el mensaje original de la excepción.

La respuesta utiliza:

```json
{
    "message": "Error interno del servidor",
    "status": 500,
    "error": {}
}
```

De esta forma se evita revelar información interna como consultas SQL, nombres de tablas, rutas del servidor u otros detalles sensibles.

### Protección contra intentos repetidos de login

El endpoint:

```http
POST /api/v1/auth/login
```

limita los intentos fallidos de autenticación.

El límite se calcula utilizando la combinación de:

- email normalizado;
- dirección IP del cliente.

El email se normaliza eliminando espacios al inicio y al final y convirtiéndolo a minúsculas.

Se permiten hasta **5 intentos fallidos por minuto** para la misma combinación de email e IP.

Los cinco primeros intentos con credenciales incorrectas responden:

```text
HTTP 401 Unauthorized
```

Al superar el límite, la API responde:

```text
HTTP 429 Too Many Requests
```

Ejemplo:

```json
{
    "message": "Demasiados intentos de inicio de sesión. Intente nuevamente más tarde.",
    "status": 429,
    "retry_after": 60,
    "error": {}
}
```

La respuesta también incluye el header HTTP:

```http
Retry-After: 60
```

El valor indica la cantidad de segundos restantes antes de poder volver a intentar el inicio de sesión.

Cuando el usuario inicia sesión correctamente, los intentos fallidos anteriores para esa combinación de email e IP son eliminados.

### Protección de secretos

Los archivos de entorno sensibles se encuentran excluidos del control de versiones mediante `.gitignore`.

Entre ellos:

```text
.env
.env.backup
.env.production
```

El archivo `.env.example` conserva únicamente la variable:

```env
JWT_SECRET=
```

Cada desarrollador debe generar su propio secreto JWT localmente:

```bash
php artisan jwt:secret
```

El valor generado no debe subirse al repositorio.

### Pruebas WAL-020

Se agregaron tests de integración para comprobar:

- bloqueo después de cinco intentos fallidos de login;
- normalización del email utilizado por el limitador;
- limpieza de intentos fallidos después de un login exitoso;
- respuesta `401` JSON sin header `Accept`;
- respuesta `403` para usuarios sin permisos administrativos;
- respuesta `404` JSON para rutas inexistentes;
- respuesta `422` JSON para errores de validación;
- respuesta `500` sin exposición de información interna.

Para ejecutar específicamente los tests de WAL-020:

```bash
php artisan test tests/Feature/HttpSecurityTest.php
```

Para ejecutar toda la suite:

```bash
php artisan test
```
## WAL-022 — Documentación de la API con Swagger

La API está documentada con OpenAPI y se puede probar desde `http://localhost:8000/api/documentation`.

Guía de uso, formato de errores, paginación y advertencias: [docs/swagger.md](docs/swagger.md).

## 👥 Integrantes del Squad 1 Laravel

- **Héctor Darío Sol** - [@hectordsol](https://github.com/hectordsol)
- **Alejandro Ramirez** - [@ramirezaed](https://github.com/ramirezaed)
- **Julio Andres** - [@JulioAndres2021](https://github.com/JulioAndres2021)
- **David Mach** - [@dav-mach](https://github.com/dav-mach)
- **Manuel Coria** - [@coriawork](https://github.com/coriawork)

## 👥 Mentor

- **Jean Paul Ferreira** - [@JePaFe](https://github.com/JePaFe)
