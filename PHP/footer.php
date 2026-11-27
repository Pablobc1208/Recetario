</main>
<footer class="pie">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Recetario colaborativo · Proyecto DWC, DWS y DIW</p>
        <!-- data-cc lo lee la librería y abre el panel para cambiar la elección -->
        <button type="button" class="btn btn-link btn-sm text-light" data-cc="show-preferencesModal">
            Configurar cookies
        </button>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Gestor de cookies: primero la librería y después nuestra configuración -->
<script src="https://cdn.jsdelivr.net/npm/vanilla-cookieconsent@3.1.0/dist/cookieconsent.umd.js"></script>
<script src="../JS/consentimiento-cookies.js"></script>
</body>

</html>