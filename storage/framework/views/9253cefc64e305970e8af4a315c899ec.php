<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo e(asset('assets/img/logo-ct-dark.png')); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(asset('assets/img/logo-ct-dark.png')); ?>">

    <title><?php echo e(config('app.name', 'Admin Dashboard')); ?> :: <?php echo $__env->yieldContent('title'); ?></title>

    <!--     Fonts and icons     -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet"/>
    <!-- Nucleo Icons -->
    <link href="<?php echo e(asset('assets/css/nucleo-icons.css')); ?>" rel="stylesheet"/>
    <link href="<?php echo e(asset('assets/css/nucleo-svg.css')); ?>" rel="stylesheet"/>
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link href="<?php echo e(asset('assets/css/nucleo-svg.css')); ?>" rel="stylesheet"/>
    <!-- CSS Files -->
    <link id="pagestyle" href="<?php echo e(asset('assets/css/soft-ui-dashboard.css?v=1.0.5')); ?>" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
</head>

<body class="">
<main class="main-content  mt-0">
    <?php echo $__env->yieldContent('content'); ?>
</main>
<!--   Core JS Files   -->
<script src="<?php echo e(asset('assets/js/core/popper.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/core/bootstrap.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/plugins/perfect-scrollbar.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/plugins/smooth-scrollbar.min.js')); ?>"></script>
<!-- Kanban scripts -->
<script src="<?php echo e(asset('assets/js/plugins/dragula/dragula.min.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/plugins/jkanban/jkanban.js')); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script type="text/javascript">
    <?php if(Session()->has('success')): ?>
        toastr.options = {"progressBar": true}
        toastr.success('<?php echo e(Session('success')); ?>')
    <?php endif; ?>
    <?php if(Session()->has('info')): ?>
        toastr.options = {"progressBar": true}
        toastr.info('<?php echo e(Session('info')); ?>')
    <?php endif; ?>
    <?php if(Session()->has('error')): ?>
        toastr.options = {"progressBar": true}
        toastr.error('<?php echo e(Session('error')); ?>')
    <?php endif; ?>
    <?php if(Session()->has('warning')): ?>
        toastr.options = {"progressBar": true}
        toastr.warning('<?php echo e(Session('warning')); ?>')
    <?php endif; ?>
</script>
<script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
        var options = {
            damping: '0.5'
        }
        Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
</script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>
<!-- Control Center for Soft Dashboard: parallax effects, scripts for the example pages etc -->
<script src="<?php echo e(asset('assets/js/soft-ui-dashboard.min.js?v=1.0.5')); ?>"></script>
</body>

</html>
<?php /**PATH C:\xampp\htdocs\vorbi\resources\views/admin/layouts/auth.blade.php ENDPATH**/ ?>