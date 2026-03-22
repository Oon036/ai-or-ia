<?php
require_once 'includes/db.php';
// Header includes session and user authorization
include 'includes/header.php';

$role = $_SESSION['role'] ?? '';

// For Admin: Count teachers, students, subjects
if ($role == 'admin') {
    $t_stmt = $pdo->query("SELECT COUNT(*) FROM teachers");
    $total_teachers = $t_stmt->fetchColumn();
    
    $s_stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $total_students = $s_stmt->fetchColumn();
    
    $sub_stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
    $total_subjects = $sub_stmt->fetchColumn();
}
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800 fw-bold">แดชบอร์ดภาพรวมระบบ</h1>
    </div>
    
    <?php if ($role == 'admin'): ?>
    <div class="row">
        <!-- Teachers Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 5px solid #4e73df !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#4e73df;">ครูผู้สอนทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_teachers; ?> คน</div>
                        </div>
                        <div class="col-auto float-end text-end ms-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-muted opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Students Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 5px solid #1cc88a !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#1cc88a;">นักเรียนทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_students; ?> คน</div>
                        </div>
                        <div class="col-auto float-end text-end ms-auto">
                            <i class="fas fa-user-graduate fa-2x text-muted opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Subjects Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 py-2" style="border-left: 5px solid #36b9cc !important;">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1" style="color:#36b9cc;">รายวิชาทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_subjects; ?> วิชา</div>
                        </div>
                        <div class="col-auto float-end text-end ms-auto">
                            <i class="fas fa-book fa-2x text-muted opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <!-- Attendance Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold" style="color:var(--primary-color);">สถิติการมาเรียนวันนี้</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height:300px; display:flex; justify-content:center;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grade Distribution Chart -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold" style="color:var(--primary-color);">ภาพรวมผลการเรียน (Grade Distribution)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar pt-4 pb-2" style="height:300px;">
                        <canvas id="gradeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctxAtt = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctxAtt, {
            type: 'doughnut',
            data: {
                labels: ['มาเรียน', 'ขาด', 'สาย', 'ลา'],
                datasets: [{
                    data: [80, 5, 10, 5], // Example default data
                    backgroundColor: ['#1cc88a', '#e74a3b', '#f6c23e', '#36b9cc'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)"
                }]
            },
            options: {
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });
        
        var ctxGrade = document.getElementById('gradeChart').getContext('2d');
        new Chart(ctxGrade, {
            type: 'bar',
            data: {
                labels: ['เกรด 4', 'เกรด 3', 'เกรด 2', 'เกรด 1', 'เกรด 0'],
                datasets: [{
                    label: 'จำนวนนักเรียน',
                    data: [45, 70, 50, 20, 10], // Example default data
                    backgroundColor: '#FB9B8F',
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 20 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
    </script>
    
    <?php else: ?>
        <div class="alert alert-info">
            <h4 class="alert-heading">ยินดีต้อนรับเข้าสู่ระบบ!</h4>
            <p>สวัสดีคุณ <?php echo htmlspecialchars($_SESSION['username']); ?> คุณสามารถเลือกเมนูจากแถบด้านข้างเพื่อเริ่มต้นใช้งาน</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
