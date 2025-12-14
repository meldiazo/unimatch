

## Guía básica para desplegar el proyecto 🚀

Hola,

Básicamente, hay **4 archivos clave** que deben revisar y deben estar en la raiz del proyecto para poder desplegarlo correctamente:

* `docker-compose.yml`
* `Dockerfile`
* `entrypoint.sh`
* `nginx.conf`

### 🔧 docker-compose.yml

> 📌 En el archivo `docker.md` hay más información.

### 🐳 Dockerfile

Aquí se debe modificar **lo mínimo necesario**.

* Si el contenedor lanza **errores por paquetes de PHP faltantes**, simplemente agreguen esos paquetes en el `Dockerfile`.

### ▶️ entrypoint.sh

* Si aparece algún error indicando que **falta un comando** (lo cual no debería pasar), agréguenlo en este archivo.

### 🌐 nginx.conf

Por último, deben verificar que se este usando el mismo nombre del proyecto este archivo y en el `docker-compose.yml`, siguiendo este formato:

```
[NombreDelProyecto]-laravel
```

Mas detalles esta en el Docker.md, para probarlo local y otras cosas como variables de entorno
