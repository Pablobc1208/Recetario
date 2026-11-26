# Integración continua con GitHub Actions

El workflow [`.github/workflows/ci.yml`](../.github/workflows/ci.yml) revisa el código automáticamente.

## Cuándo se ejecuta

- Al hacer `push` a las ramas `dev` o `main`.
- Al abrir o actualizar una pull request hacia `dev` o `main`.
- A mano, desde la pestaña **Actions** → **CI** → **Run workflow**.

## Qué comprueba

| Trabajo          | Qué hace                                                                                                                                                                              | Herramienta        |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------ |
| PHP (sintaxis)   | Revisa que todos los `.php` no tengan errores de sintaxis                                                                                                                             | `php -l`           |
| SQL              | Crea una base de datos MySQL 8 vacía, importa `docs/sql/schema.sql` y comprueba que existen las 5 tablas (`usuarios`, `categorias`, `recetas`, `ingredientes` y `receta_ingrediente`) | `mysql`            |
| HTML             | Ejecuta con PHP las páginas que usan la plantilla común y valida el HTML generado (también los `.html` sueltos)                                                                       | `html-validate`    |
| CSS y JavaScript | Revisa las hojas de `CSS/` y los scripts de `JS/`                                                                                                                                     | Stylelint y ESLint |
| Sin credenciales | Falla si se ha subido `config.php` o algún `.env`                                                                                                                                     | `git ls-files`     |

Si una comprobación falla, el workflow se marca en rojo en el commit y en la pull request. Antes de fusionar `dev` en `main` todas deben estar en verde.

## Ver los resultados

El badge **CI** del `README.md` muestra si la última ejecución en `dev` ha pasado (verde) o ha fallado (rojo).

1. Abrir la pestaña **Actions** del repositorio.
2. Entrar en la ejecución del commit y en el trabajo marcado en rojo para ver el error.

## Ejecutar las revisiones en local (opcional)

Con Node.js instalado, desde la raíz del repositorio:

```bash
npm install
npm run lint        # CSS y JavaScript
```

Las herramientas y los comandos están en `package.json`, y su configuración en `eslint.config.mjs`, `.stylelintrc.json` y `.htmlvalidate.json`. La carpeta `node_modules/` no se sube al repositorio.

Para probar el script de la base de datos basta con importarlo en una base de datos vacía (por ejemplo, desde phpMyAdmin o con `mysql -uroot -p < docs/sql/schema.sql`).

## Releases automáticas

El workflow [`.github/workflows/release.yml`](../.github/workflows/release.yml) se ejecuta cada vez que se fusiona algo en `main` (es decir, cada vez que llega un push a `main`). Busca la última etiqueta con el formato `vN.0` y crea la siguiente versión: la primera es `v1.0` y después `v2.0`, `v3.0`, etc. La release incluye las notas generadas por GitHub con los cambios y las pull requests desde la versión anterior.

Las releases se ven en la página **Releases** del repositorio.
