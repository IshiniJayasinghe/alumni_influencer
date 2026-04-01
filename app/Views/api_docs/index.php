<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Alumni Influencer – API Documentation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Swagger UI CSS (CDN) -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">

    <style>
        body { margin: 0; background: #fafafa; }
        #swagger-ui { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .topbar { display: none !important; }
    </style>
</head>
<body>

<div id="swagger-ui"></div>

<!-- Swagger UI JS (CDN) -->
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>

<script>
    window.onload = function () {
        SwaggerUIBundle({
            url: "<?= base_url('openapi.json') ?>",
            dom_id: '#swagger-ui',
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            layout: "StandaloneLayout",
            // Allow users to enter their Bearer token in the UI
            persistAuthorization: true,
            deepLinking: true,
            displayOperationId: false,
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 1,
            docExpansion: "list"
        });
    };
</script>

</body>
</html>
