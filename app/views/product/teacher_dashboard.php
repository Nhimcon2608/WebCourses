                    <ul class="sidebar-menu">
                        <li class="<?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                            <a href="teacher_dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Tổng quan</span>
                            </a>
                        </li>
                        <li class="<?php echo $active_tab === 'courses' ? 'active' : ''; ?>">
                            <a href="teacher_dashboard.php?tab=courses">
                                <i class="fas fa-book"></i>
                                <span>Khóa học của tôi</span>
                            </a>
                        </li>
                        <li class="<?php echo $active_tab === 'assignments' ? 'active' : ''; ?>">
                            <a href="teacher_grade_assignments.php">
                                <i class="fas fa-tasks"></i>
                                <span>Chấm bài tập</span>
                            </a>
                        </li>
                        <li class="<?php echo $active_tab === 'students' ? 'active' : ''; ?>">
                            <a href="teacher_dashboard.php?tab=students">
                                <i class="fas fa-users"></i>
                                <span>Sinh viên</span>
                            </a>
                        </li>
                        <li class="<?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                            <a href="teacher_dashboard.php?tab=settings">
                                <i class="fas fa-cog"></i>
                                <span>Cài đặt</span>
                            </a>
                        </li>
                    </ul> 