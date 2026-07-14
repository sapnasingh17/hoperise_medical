<header class="header">
            <div class="page-brand">
                <a class="link" href="index.php">
                    <span class="brand">Hope
                        <span class="brand-tip">Rise</span>
                    </span>
                    <span class="brand-mini">HR</span>
                </a>
            </div>
            <div class="flexbox flex-1">
                <!-- START TOP-LEFT TOOLBAR-->
                <ul class="nav navbar-toolbar">
                    <li>
                        <a class="nav-link sidebar-toggler js-sidebar-toggler"><i class="ti-menu"></i></a>
                    </li>
                   
                </ul>
                <!-- END TOP-LEFT TOOLBAR-->
                <!-- START TOP-RIGHT TOOLBAR-->
                <ul class="nav navbar-toolbar">
                   
                    <li class="dropdown dropdown-user">
                        <a class="nav-link dropdown-toggle link" data-bs-toggle="dropdown">
                            <img src="<?php echo esc($user_avatar); ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" />
                            <span></span><?php echo esc($display_name); ?><i class="fa fa-angle-down m-l-5"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="profile.php"><i class="fa fa-user"></i>Profile</a>
                            <a class="dropdown-item" href="change_password.php"><i class="fa fa-cog"></i>Change Password</a>
                            <li class="dropdown-divider"></li>
                            <a class="dropdown-item" href="logout.php"><i class="fa fa-power-off"></i>Logout</a>
                        </ul>
                    </li>
                </ul>
                <!-- END TOP-RIGHT TOOLBAR-->
            </div>
        </header>