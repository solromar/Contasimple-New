# Contasimple-New

# Obtencion de Facturas Emitidas y Recibidas desde la API de Contasimple

## Requisitos previos
**Clave de Autorización:** Para acceder a la API de Contasimple, necesitas una clave de autorización que nos la otorga el cliente de Contasimple desde la pantalla de "Aplicaciones Externas".

**--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------**
 **Token de Acceso:** Se llama a la Api de Auth para obtener un token de acceso utilizando la clave de autorización para autenticarte en la API.El "access_token" obtenido es válido durante un tiempo limitado (por defecto 1 hora).
 Pasado este tiempo el token caduca pero es posible obtener un nuevo token realizando de nuevo el proceso de login.
**Facturas:** Para obtener las facturas tanto emitidas como recibidas, hay que pasarle a la URL la version de la API (la actual es la 2) y el periodo que se quiere consultar, este ultimo dato es OBLIGATORIO y en formato YYYY-nT, donde n es el trimestre (1,2,3 y4)

   



