<?php
session_start();

// Initialize tasks array in session if it doesn't exist
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (!empty($_POST['task_title'])) {
                    $task = [
                        'id' => uniqid(),
                        'title' => htmlspecialchars($_POST['task_title']),
                        'description' => htmlspecialchars($_POST['task_description'] ?? ''),
                        'priority' => $_POST['priority'] ?? 'medium',
                        'completed' => false,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    $_SESSION['tasks'][] = $task;
                }
                break;
                
            case 'toggle':
                $taskId = $_POST['task_id'];
                foreach ($_SESSION['tasks'] as &$task) {
                    if ($task['id'] === $taskId) {
                        $task['completed'] = !$task['completed'];
                        break;
                    }
                }
                break;
                
            case 'delete':
                $taskId = $_POST['task_id'];
                $_SESSION['tasks'] = array_filter($_SESSION['tasks'], function($task) use ($taskId) {
                    return $task['id'] !== $taskId;
                });
                break;
                
            case 'edit':
                $taskId = $_POST['task_id'];
                foreach ($_SESSION['tasks'] as &$task) {
                    if ($task['id'] === $taskId) {
                        $task['title'] = htmlspecialchars($_POST['task_title']);
                        $task['description'] = htmlspecialchars($_POST['task_description']);
                        $task['priority'] = $_POST['priority'];
                        break;
                    }
                }
                break;
        }
    }
}

// Get filter and sort parameters
$filter = $_GET['filter'] ?? 'all';
$sort = $_GET['sort'] ?? 'created';

// Filter tasks
$filteredTasks = $_SESSION['tasks'];
if ($filter === 'completed') {
    $filteredTasks = array_filter($filteredTasks, function($task) {
        return $task['completed'];
    });
} elseif ($filter === 'pending') {
    $filteredTasks = array_filter($filteredTasks, function($task) {
        return !$task['completed'];
    });
}

// Sort tasks
usort($filteredTasks, function($a, $b) use ($sort) {
    switch ($sort) {
        case 'priority':
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            return $priorityOrder[$b['priority']] - $priorityOrder[$a['priority']];
        case 'title':
            return strcasecmp($a['title'], $b['title']);
        case 'created':
        default:
            return strtotime($b['created_at']) - strtotime($a['created_at']);
    }
});

// Count tasks
$totalTasks = count($_SESSION['tasks']);
$completedTasks = count(array_filter($_SESSION['tasks'], function($task) {
    return $task['completed'];
}));
$pendingTasks = $totalTasks - $completedTasks;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Task Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }

        .main-content {
            padding: 30px;
        }

        .task-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4facfe;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #51cf66 0%, #40c057 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffd43b 0%, #fab005 100%);
            color: white;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
        }

        .filter-group select {
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            background: white;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .task-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .task-card.completed {
            background: #f8f9fa;
            border-color: #51cf66;
        }

        .task-card.completed .task-title {
            text-decoration: line-through;
            color: #6c757d;
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .task-title {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .task-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .task-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #666;
        }

        .priority-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-high {
            background: #ffe0e0;
            color: #d63384;
        }

        .priority-medium {
            background: #fff3cd;
            color: #fd7e14;
        }

        .priority-low {
            background: #d1ecf1;
            color: #0dcaf0;
        }

        .task-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .no-tasks {
            text-align: center;
            color: #666;
            font-size: 1.2em;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .edit-form {
            display: none;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .edit-form.active {
            display: block;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .tasks-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Task Manager</h1>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $totalTasks; ?></span>
                    <span>Total Tasks</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $pendingTasks; ?></span>
                    <span>Pending</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $completedTasks; ?></span>
                    <span>Completed</span>
                </div>
            </div>
        </div>

        <div class="main-content">
            <!-- Add Task Form -->
            <div class="task-form">
                <h2>Add New Task</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label for="task_title">Task Title *</label>
                        <input type="text" id="task_title" name="task_title" required>
                    </div>
                    <div class="form-group">
                        <label for="task_description">Description</label>
                        <textarea id="task_description" name="task_description" rows="3"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Add Task</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label>Filter:</label>
                    <select onchange="window.location.href='?filter=' + this.value + '&sort=<?php echo $sort; ?>'">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Tasks</option>
                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sort by:</label>
                    <select onchange="window.location.href='?filter=<?php echo $filter; ?>&sort=' + this.value">
                        <option value="created" <?php echo $sort === 'created' ? 'selected' : ''; ?>>Date Created</option>
                        <option value="priority" <?php echo $sort === 'priority' ? 'selected' : ''; ?>>Priority</option>
                        <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                    </select>
                </div>
            </div>

            <!-- Tasks Display -->
            <?php if (empty($filteredTasks)): ?>
                <div class="no-tasks">
                    <?php if ($filter === 'all'): ?>
                        No tasks yet. Add your first task above! 🎯
                    <?php elseif ($filter === 'completed'): ?>
                        No completed tasks yet. Keep working! 💪
                    <?php else: ?>
                        No pending tasks. Great job! 🎉
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="tasks-grid">
                    <?php foreach ($filteredTasks as $task): ?>
                        <div class="task-card <?php echo $task['completed'] ? 'completed' : ''; ?>">
                            <div class="task-header">
                                <h3 class="task-title"><?php echo $task['title']; ?></h3>
                                <span class="priority-badge priority-<?php echo $task['priority']; ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($task['description'])): ?>
                                <p class="task-description"><?php echo $task['description']; ?></p>
                            <?php endif; ?>
                            
                            <div class="task-meta">
                                <span>Created: <?php echo date('M j, Y g:i A', strtotime($task['created_at'])); ?></span>
                                <span><?php echo $task['completed'] ? '✅ Completed' : '⏳ Pending'; ?></span>
                            </div>
                            
                            <div class="task-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-small <?php echo $task['completed'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <?php echo $task['completed'] ? 'Mark Pending' : 'Mark Complete'; ?>
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-small btn-primary" onclick="toggleEdit('<?php echo $task['id']; ?>')">
                                    Edit
                                </button>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            </div>
                            
                            <!-- Edit Form -->
                            <div id="edit-<?php echo $task['id']; ?>" class="edit-form">
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <div class="form-group">
                                        <label>Task Title</label>
                                        <input type="text" name="task_title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="task_description" rows="3"><?php echo htmlspecialchars($task['description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Priority</label>
                                        <select name="priority">
                                            <option value="low" <?php echo $task['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                            <option value="medium" <?php echo $task['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="high" <?php echo $task['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                        </select>
                                    </div>
                                    <div style="display: flex; gap: 10px;">
                                        <button type="submit" class="btn btn-small btn-success">Save Changes</button>
                                        <button type="button" class="btn btn-small btn-warning" onclick="toggleEdit('<?php echo $task['id']; ?>')">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEdit(taskId) {
            const editForm = document.getElementById('edit-' + taskId);
            editForm.classList.toggle('active');
        }

        // Auto-hide success messages or notifications
        document.addEventListener('DOMContentLoaded', function() {
            // Add any additional JavaScript functionality here
            console.log('Task Manager loaded successfully!');
        });
    </script>
</body>
</html>