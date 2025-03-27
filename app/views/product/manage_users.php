<?php
define('BASE_URL', '/WebCourses/');
include '../../config/connect.php';
session_start();

// Kiểm tra phân quyền admin
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
//     header("Location: home.php");
//     exit();
// }

// Truy vấn danh sách người dùng
$result = $conn->query("SELECT user_id, username, email, password, role, created_at FROM users ORDER BY created_at DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);

// Xử lý thông báo
$success_message = isset($_GET['success']) ? $_GET['success'] : '';
$error_message = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng - Học Tập Trực Tuyến</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Reset color scheme */
        body {
            color: #333;
            background-color: #f5f7fa;
        }
        
        /* Table styling */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .users-table th, .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            color: #333; /* Đảm bảo nội dung có màu đen */
            font-weight: normal;
        }
        
        .users-table th {
            background: #1e3c72;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: none;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .users-table tr:nth-child(even) {
            background-color: #f9fafc;
        }
        
        .users-table tr:hover {
            background-color: #f0f4ff;
            transition: background-color 0.3s ease;
        }
        
        /* Password column */
        .password-column {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-family: monospace;
            color: #666;
            background-color: #f5f5f5;
            border-radius: 4px;
            padding: 2px 4px !important;
        }
        
        /* Action buttons */
        .action-column {
            display: flex;
            gap: 8px;
        }
        
        .btn-edit, .btn-delete {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-edit i, .btn-delete i {
            margin-right: 6px;
        }
        
        .btn-edit {
            background: #4aa1ff;
            color: white;
        }
        
        .btn-edit:hover {
            background: #1e88e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .btn-delete {
            background: #ff5252;
            color: white;
        }
        
        .btn-delete:hover {
            background: #d32f2f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        /* Message styling */
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .success {
            background-color: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }
        
        .error {
            background-color: #ffebee;
            border-left-color: #f44336;
            color: #c62828;
        }
        
        /* Add user button */
        .add-user-btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 20px;
            background: linear-gradient(to right, #4CAF50, #45a049);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }
        
        .add-user-btn i {
            margin-right: 8px;
        }
        
        .add-user-btn:hover {
            background: linear-gradient(to right, #45a049, #388E3C);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(76, 175, 80, 0.4);
        }
        
        /* Section styling */
        #dashboard {
            padding: 40px 0;
        }
        
        #dashboard h2 {
            color: #1e3c72;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        #dashboard p {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        /* Responsive styling */
        @media screen and (max-width: 992px) {
            .users-table th, .users-table td {
                padding: 12px;
                font-size: 14px;
            }
        }
        
        @media screen and (max-width: 768px) {
            .users-table th, .users-table td {
                padding: 10px 8px;
                font-size: 13px;
            }
            
            .action-column {
                flex-direction: column;
                gap: 6px;
            }
            
            .btn-edit, .btn-delete {
                width: 100%;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container header-container">
            <div class="logo">EduHub</div>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Trang Chủ</a></li>
                    <li><a href="manage_users.php" class="btn">Quản lý Người Dùng</a></li>
                    <li><a href="manage_courses.php" class="btn">Quản lý Khóa Học</a></li>
                    <li><a href="<?php echo BASE_URL; ?>app/controllers/logout.php" class="btn">Đăng xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="dashboard">
        <div class="container">
            <h2>Quản Lý Người Dùng</h2>
            <p>Quản lý tài khoản người dùng trong hệ thống</p>
            
            <?php if($success_message): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if($error_message): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <a href="add_user.php" class="add-user-btn">
                <i class="fas fa-plus"></i> Thêm Người Dùng Mới
            </a>
            
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="15%">Tên đăng nhập</th>
                            <th width="20%">Email</th>
                            <th width="20%">Mật khẩu</th>
                            <th width="10%">Vai trò</th>
                            <th width="15%">Ngày tạo</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="password-column"><?php echo htmlspecialchars($user['password']); ?></td>
                            <td>
                                <span class="role-badge <?php echo strtolower($user['role']); ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td class="action-column">
                                <a href="edit_user.php?user_id=<?php echo $user['user_id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <a href="delete_user.php?user_id=<?php echo $user['user_id']; ?>" class="btn-delete" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này không?')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>© 2025 Học Tập Trực Tuyến. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.5/ScrollTrigger.min.js"></script>
    <script src="<?php echo BASE_URL; ?>public/js/script.js"></script>

    <style>
        /* Thêm badge cho vai trò */
        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .role-badge.admin {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .role-badge.instructor {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .role-badge.student {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
    </style>
</body>
</html>
