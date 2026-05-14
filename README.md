# Shopify Mastery

Este proyecto es una plataforma de aprendizaje para Shopify con documentación en Markdown, retos prácticos y un panel de administración simple.

## Qué puedes hacer

- Ver documentación y tutoriales en formato Markdown.
- Navegar los retos y ejercicios desde el panel principal.
- Usar el panel de administración para ocultar o eliminar documentos.
- Crear y gestionar categorías para los retos.

## Cómo usarlo (sin saber programar)

### 1. Iniciar la plataforma

1. Abre el terminal o la aplicación de línea de comandos.
2. Ve a la carpeta del proyecto:
   ```powershell
   cd C:\Users\Frank\Desktop\shopify-web
   ```
3. Arranca el proyecto con Docker:
   ```powershell
   docker-compose up
   ```
4. Abre un navegador y entra a:
   - `http://localhost:8080` para ver la plataforma

> Si ves una página con el sitio, ya está funcionando.

### 2. Ver la documentación

- Haz clic en el enlace `Documentación` en la barra lateral.
- La página muestra todos los archivos Markdown disponibles.
- Si algo está oculto, no aparecerá en esta lista.

### 3. Usar el panel de administración

- Abre `http://localhost:8080/src/admin.php` o haz clic en `Panel Admin` en la barra lateral.
- Credenciales por defecto:
  - Usuario: `superuser`
  - Contraseña: `super1user2`

#### Qué puedes hacer en admin:

- Ocultar documentos Markdown para que no se vean en la documentación.
- Eliminar documentos si ya no se necesitan.
- Crear nuevas categorías para organizar los retos.
- Eliminar categorías existentes.

### 4. Añadir nuevos documentos de ayuda

Los archivos de documentación están en:

- `src/docs/md/`

Cada archivo con extensión `.md` se convierte en una página de documentación automática.

### 5. Ocultar documentos sin borrarlos

Cuando un documento se oculta desde el admin, el sistema lo recuerda automáticamente usando un archivo de configuración.

### 6. Categorías de ejercicios

- Las categorías se guardan en la base de datos.
- Al crear un nuevo reto, puedes seleccionar la categoría disponible.
- Si no hay categorías, crea una desde el admin antes de usar el selector.

## Qué no necesitas saber para usarlo

- No necesitas editar código para ver la plataforma.
- No necesitas saber PHP o bases de datos para usar la documentación y el panel admin.
- Solo necesitas un navegador y Docker funcionando.

## Notas útiles

- Si el proyecto no arranca, asegúrate de tener Docker Desktop abierto.
- Si quieres cerrar la plataforma, presiona `Ctrl+C` en el terminal donde ejecutaste `docker-compose up`.
- No hace falta modificar nada en `src/admin.php` o `src/docs.php` para usar las funciones básicas.

---

## Estructura principal del proyecto

- `docker-compose.yml`: configura la plataforma y la base de datos.
- `src/`: carpeta principal con todos los archivos PHP y documentación.
- `src/docs/md/`: aquí van los documentos que se muestran como páginas de ayuda.
- `src/admin.php`: panel de administración.
- `src/docs.php`: página pública de documentación.
- `src/sidebar.php`: menú lateral del sitio.

¡Listo! Con esto puedes usar la plataforma sin necesidad de programar.