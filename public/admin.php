<?php

require_once __DIR__ . '/../vendor/autoload.php';

use YoutubeChatCapture\Models\Stream;

$action = $_POST['action'] ?? '';
$message = '';

if ($action === 'add') {
    Stream::create([
        'youtube_url' => $_POST['youtube_url'],
        'is_active' => isset($_POST['is_active'])
    ]);
    $message = 'Stream added successfully!';
} elseif ($action === 'update') {
    $stream = new Stream();
    $stream->id = $_POST['id'];
    $stream->update([
        'youtube_url' => $_POST['youtube_url'],
        'is_active' => isset($_POST['is_active'])
    ]);
    $message = 'Stream updated successfully!';
} elseif ($action === 'delete') {
    $stream = new Stream();
    $stream->id = $_POST['id'];
    $stream->delete();
    $message = 'Stream deleted successfully!';
}

$streams = Stream::findAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Stream Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>YouTube Stream Manager</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h3>Add New Stream</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">YouTube URL</label>
                        <input type="text" name="youtube_url" class="form-control" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Stream</button>
                </form>
            </div>
        </div>

        <h3>Existing Streams</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>YouTube URL</th>
                        <th>Live Chat ID</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($streams as $stream): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stream->id); ?></td>
                            <td><?php echo htmlspecialchars($stream->youtube_url); ?></td>
                            <td><?php echo htmlspecialchars($stream->live_chat_id ?? 'Not set'); ?></td>
                            <td>
                                <span class="badge <?php echo $stream->is_active ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $stream->is_active ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($stream->created_at); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $stream->id; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $stream->id; ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $stream->id; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Stream</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo $stream->id; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">YouTube URL</label>
                                                <input type="text" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($stream->youtube_url); ?>" required>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" <?php echo $stream->is_active ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Active</label>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 