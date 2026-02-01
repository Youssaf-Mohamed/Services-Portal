<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e(config('app.name', 'Laravel')); ?></title>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js', 'resources/js/main.js']); ?>
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
<?php /**PATH E:\laravel\Services-Portal\backend\resources\views/app.blade.php ENDPATH**/ ?>