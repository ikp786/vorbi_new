<nav class="navbar navbar-main navbar-expand-lg position-sticky mt-4 top-1 px-0 mx-4 shadow-none border-radius-xl z-index-sticky" id="navbarBlur" data-scroll="true">
    <div class="container-fluid py-1 px-3">
        <?php echo $__env->yieldContent('breadcrumb'); ?>
        <div class="sidenav-toggler sidenav-toggler-inner d-xl-block d-none ">
            <a href="javascript:;" class="nav-link text-body p-0">
                <div class="sidenav-toggler-inner">
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                    <i class="sidenav-toggler-line"></i>
                </div>
            </a>
        </div>
        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
            <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                
            </div>
            <ul class="navbar-nav  justify-content-end">
                
                <li class="nav-item dropdown d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        
                        <img src="<?php echo e(asset('assets/img/team-2.jpg')); ?>" class="avatar avatar-sm me-1" alt="user image">
                        <span class="d-sm-inline d-none me-1 ">
                            
                        </span>
                        <i class="fa fa-angle-down cursor-pointer"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
                        
                        <li class="mb-2">
                            
                                    <a class="dropdown-item" href="<?php echo e(route('admin.logout')); ?>"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-admin').submit();">
                                        <?php echo e(__('Logout')); ?>

                                    </a>

                                    <form id="logout-admin" action="<?php echo e(route('admin.logout')); ?>" method="POST" class="d-none">
                                        <?php echo csrf_field(); ?>
                                    </form>
                                
                        </li>
                        
                    </ul>
                </li>
                <li class="nav-item d-xl-none ps-3 d-flex align-items-center">
                    <a href="javascript:;" class="nav-link text-body p-0" id="iconNavbarSidenav">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </a>
                </li>
                
            </ul>
        </div>
    </div>
</nav>
<?php /**PATH C:\xampp\htdocs\vorbi\resources\views/admin/layouts/header.blade.php ENDPATH**/ ?>