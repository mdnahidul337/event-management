<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/github_upload.php';

$action = $_GET['action'] ?? 'list';
$edit_id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Auto-migrate new columns
try { $pdo->exec("ALTER TABLE events ADD COLUMN end_date DATETIME DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN fee DECIMAL(10,2) DEFAULT 0"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN image VARCHAR(255) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN tags VARCHAR(500) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS meta_description VARCHAR(300) DEFAULT NULL"); } catch (PDOException $e) {}


$can_manage_events = $_SESSION['role_level'] >= 50;

$can_manage_events = $_SESSION['role_level'] >= 50;

// ─── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_manage_events) {

    // ── GitHub image upload ──
    $image_filename = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $ext      = strtolower(pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION));
        $fname    = 'evt_' . time() . '_' . uniqid() . '.' . $ext;
        $cdn_url  = github_upload($_FILES['event_image']['tmp_name'], $fname, 'events');
        if ($cdn_url) {
            $image_filename = $cdn_url; // Store full CDN URL in DB
        } else {
            $error = "Image upload to GitHub failed. Event saved without image.";
        }
    }

    if (isset($_POST['add_event'])) {
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $location    = trim($_POST['location']);
        $start_date  = $_POST['start_date'];
        $end_date    = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $type        = $_POST['type'];
        $fee         = $type === 'Paid' ? floatval($_POST['fee']) : 0;
        $tags        = trim($_POST['tags']);
        $meta_desc   = trim($_POST['meta_description']);

        $stmt = $pdo->prepare("INSERT INTO events (title, description, location, start_date, end_date, type, fee, image, tags, meta_description, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $location, $start_date, $end_date, $type, $fee, $image_filename, $tags, $meta_desc, $_SESSION['user_id']])) {
            $event_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'CREATE', 'event', ?, ?)")
                ->execute([$_SESSION['user_id'], "Created event: $title (ID $event_id)", $_SERVER['REMOTE_ADDR']]);
            $success = "Event created successfully!";
            $action = 'list';
        } else {
            $error = "Failed to create event.";
        }
    }

    if (isset($_POST['edit_event'])) {
        $eid         = intval($_POST['event_id']);
        $title       = trim($_POST['title']);
        $description = trim($_POST['description']);
        $location    = trim($_POST['location']);
        $start_date  = $_POST['start_date'];
        $end_date    = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $type        = $_POST['type'];
        $fee         = $type === 'Paid' ? floatval($_POST['fee']) : 0;
        $tags        = trim($_POST['tags']);
        $tags        = trim($_POST['tags']);
        $meta_desc   = trim($_POST['meta_description']);

        // Keep old image if no new upload
        if (!$image_filename) {
            $old = $pdo->prepare("SELECT image FROM events WHERE id=?");
            $old->execute([$eid]);
            $image_filename = $old->fetchColumn();
        }

        $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, location=?, start_date=?, end_date=?, type=?, fee=?, image=?, tags=?, meta_description=? WHERE id=?");
        if ($stmt->execute([$title, $description, $location, $start_date, $end_date, $type, $fee, $image_filename, $tags, $meta_desc, $eid])) {
            $pdo->prepare("INSERT INTO activity_logs (user_id, action_type, module, details, ip_address) VALUES (?, 'UPDATE', 'event', ?, ?)")
                ->execute([$_SESSION['user_id'], "Updated event ID $eid", $_SERVER['REMOTE_ADDR']]);
            $success = "Event updated successfully!";
            $action = 'list';
        } else {
            $error = "Failed to update event.";
        }
    }

    if (isset($_POST['delete_event'])) {
        $eid = intval($_POST['event_id']);
        // Get image URL before delete (for GitHub cleanup)
        $old_img = $pdo->prepare("SELECT image FROM events WHERE id=?");
        $old_img->execute([$eid]);
        $old_img_url = $old_img->fetchColumn();

        // Nullify FK in payments first to avoid constraint error
        $pdo->prepare("UPDATE payments SET event_id = NULL WHERE event_id = ?")->execute([$eid]);

        // Now safe to delete
        $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$eid]);

        // Optionally remove image from GitHub
        if ($old_img_url && str_contains($old_img_url, 'cdn.jsdelivr.net')) {
            github_delete($old_img_url);
        }

        $success = "Event deleted.";
        $action = 'list';
    }
}

// ─── Fetch data ──────────────────────────────────────────────────────────────
$events   = [];
$edit_evt = null;

if ($action === 'list') {
    $events = $pdo->query("SELECT e.*, u.name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.start_date DESC")->fetchAll();
}
if ($action === 'edit' && $edit_id && $can_manage_events) {
    $s = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $s->execute([$edit_id]);
    $edit_evt = $s->fetch();
    if (!$edit_evt) { $action = 'list'; $events = $pdo->query("SELECT e.*, u.name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.start_date DESC")->fetchAll(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - SCC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-container { background: var(--card-bg); padding: 2rem; border-radius: var(--radius); box-shadow: var(--shadow-sm); max-width: 900px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-main); }
        .form-control { width: 100%; padding: 0.8rem 1rem; border: 1.5px solid var(--border-color); border-radius: var(--radius); outline: none; background: var(--bg-color); color: var(--text-main); font-family: 'Inter', sans-serif; transition: border-color 0.2s; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; }
        .alert { padding: 1rem 1.2rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #047857; border-left: 4px solid #10b981; }
        .alert-error   { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
        .section-label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); }
        .tag-hint { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.3rem; }
        .event-img-preview { width: 100%; max-height: 180px; object-fit: cover; border-radius: var(--radius); margin-top: 0.75rem; border: 1.5px solid var(--border-color); }
        .event-banner { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
        .btn-icon { padding: 0.35rem 0.7rem; font-size: 0.8rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-edit   { background: #e0e7ff; color: #3730a3; }
        .btn-delete { background: #fee2e2; color: #b91c1c; }
        .fee-field  { display: none; }
        .fee-field.show { display: block; }
        @media (max-width: 768px) {
            .form-grid, .form-grid-3 { grid-template-columns: 1fr; gap: 1rem; }
            .form-container { padding: 1.5rem; }
            .content-area { padding: 1rem; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="content-area">
        <?php if ($success): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div><?php endif; ?>

        <?php if ($action === 'list'): ?>
        <!-- ═══════════════════ LIST VIEW ═══════════════════ -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
            <h2><i class="fa-solid fa-calendar-check"></i> Manage Events</h2>
            <?php if ($can_manage_events): ?>
                <a href="events.php?action=add" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Event</a>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title & Tags</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Status</th>
                        <?php if ($can_manage_events): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $e): ?>
                    <tr>
                        <td>
                            <?php if ($e['image']): ?>
                                <img src="<?php echo (strpos($e['image'], 'http') === 0) ? $e['image'] : '../assets/image/Events/' . htmlspecialchars($e['image']); ?>" class="event-banner" alt="">
                            <?php else: ?>
                                <div style="width:60px;height:40px;background:#e5e7eb;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#9ca3af;"><i class="fa-solid fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($e['title']); ?></strong>
                            <?php if ($e['tags']): ?>
                                <div style="margin-top:4px;">
                                    <?php foreach (explode(',', $e['tags']) as $tag): ?>
                                        <span style="display:inline-block;background:#e0e7ff;color:#3730a3;border-radius:20px;padding:1px 8px;font-size:0.72rem;margin:2px 2px 0 0;">#<?php echo htmlspecialchars(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($e['start_date'])); ?>
                            <?php if ($e['end_date']): ?><br><span style="color:var(--text-muted);font-size:0.8rem;">→ <?php echo date('M d, Y', strtotime($e['end_date'])); ?></span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($e['location']); ?></td>
                        <td>
                            <?php if ($e['type'] === 'Paid'): ?>
                                <span class="badge" style="background:#fef3c7;color:#92400e;">৳<?php echo number_format($e['fee'],2); ?></span>
                            <?php else: ?>
                                <span class="badge" style="background:#d1fae5;color:#065f46;">Free</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_styles = ['Upcoming'=>'#3b82f6','Ongoing'=>'#10b981','Completed'=>'#6b7280'];
                            $sc = $status_styles[$e['status']] ?? '#6b7280';
                            ?>
                            <span style="color:<?php echo $sc; ?>;font-weight:600;"><i class="fa-solid fa-circle" style="font-size:0.5rem;vertical-align:middle;"></i> <?php echo $e['status']; ?></span>
                        </td>
                        <?php if ($can_manage_events): ?>
                        <td style="white-space:nowrap;">
                            <a href="event_participants.php?id=<?php echo $e['id']; ?>" class="btn-icon" style="background:#f3f4f6; color:#4b5563;" title="View Participants"><i class="fa-solid fa-users"></i></a>
                            <a href="events.php?action=edit&id=<?php echo $e['id']; ?>" class="btn-icon btn-edit"><i class="fa-solid fa-pen"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                                <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                                <button type="submit" name="delete_event" class="btn-icon btn-delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($events)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fa-solid fa-calendar-xmark" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i> No events yet. Create your first event!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php elseif (($action === 'add' || $action === 'edit') && $can_manage_events): ?>
        <!-- ═══════════════════ ADD / EDIT FORM ═══════════════════ -->
        <div style="margin-bottom:2rem;">
            <a href="events.php" style="color:var(--text-muted);text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Events</a>
            <h2 style="margin-top:0.75rem;"><?php echo $action === 'edit' ? '<i class="fa-solid fa-pen"></i> Edit Event' : '<i class="fa-solid fa-plus"></i> Create New Event'; ?></h2>
        </div>

        <div class="form-container">
            <form action="events.php?action=<?php echo $action; ?><?php echo $action==='edit' ? '&id='.$edit_id : ''; ?>" method="POST" enctype="multipart/form-data">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="event_id" value="<?php echo $edit_evt['id']; ?>">
                <?php endif; ?>

                <!-- ── Basic Info ── -->
                <div class="section-label">Basic Information</div>
                <div class="form-group">
                    <label>Event Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Enter a clear, descriptive title" value="<?php echo htmlspecialchars($edit_evt['title'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="Detailed description of the event..."><?php echo htmlspecialchars($edit_evt['description'] ?? ''); ?></textarea>
                </div>

                <!-- ── Date & Location ── -->
                <div class="section-label" style="margin-top:2rem;">Date, Time & Location</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fa-solid fa-calendar-day"></i> Start Date & Time</label>
                        <input type="datetime-local" name="start_date" class="form-control" value="<?php echo $edit_evt ? date('Y-m-d\TH:i', strtotime($edit_evt['start_date'])) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fa-solid fa-calendar-check"></i> End Date & Time (optional)</label>
                        <input type="datetime-local" name="end_date" class="form-control" value="<?php echo ($edit_evt && $edit_evt['end_date']) ? date('Y-m-d\TH:i', strtotime($edit_evt['end_date'])) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fa-solid fa-location-dot"></i> Location / Venue</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g. SCC Computer Lab, Room 201" value="<?php echo htmlspecialchars($edit_evt['location'] ?? ''); ?>" required>
                </div>

                <!-- ── Type & Fee ── -->
                <div class="section-label" style="margin-top:2rem;">Event Type & Pricing</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Event Type</label>
                        <select name="type" id="eventType" class="form-control" onchange="toggleFee(this.value)">
                            <option value="Free"  <?php echo (($edit_evt['type'] ?? 'Free') === 'Free')  ? 'selected' : ''; ?>>Free</option>
                            <option value="Paid"  <?php echo (($edit_evt['type'] ?? '') === 'Paid')       ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="form-group fee-field <?php echo (($edit_evt['type'] ?? '') === 'Paid') ? 'show' : ''; ?>" id="feeField">
                        <label><i class="fa-solid fa-bangladeshi-taka-sign"></i> Entry Fee (৳)</label>
                        <input type="number" name="fee" class="form-control" min="0" step="0.01" placeholder="0.00" value="<?php echo htmlspecialchars($edit_evt['fee'] ?? '0'); ?>">
                    </div>
                </div>

                <?php if ($action === 'edit'): ?>
                <div class="form-group" style="background:#f9fafb; padding:1rem; border-radius:6px; border:1px solid #e5e7eb;">
                    <label style="margin-bottom:0.25rem;">Event Status</label>
                    <?php
                    $status_styles = ['Upcoming'=>'#3b82f6','Ongoing'=>'#10b981','Completed'=>'#6b7280'];
                    $sc = $status_styles[$edit_evt['status']] ?? '#6b7280';
                    ?>
                    <div style="color:<?php echo $sc; ?>; font-weight:700; font-size:1.1rem;">
                        <i class="fa-solid fa-circle" style="font-size:0.6rem; vertical-align:middle;"></i> 
                        <?php echo $edit_evt['status']; ?>
                        <span style="font-weight:400; font-size:0.8rem; color:var(--text-muted); margin-left:10px;">(Automatically managed based on dates)</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ── Image ── -->
                <div class="section-label" style="margin-top:2rem;">Event Banner Image</div>
                <div class="form-group">
                    <label><i class="fa-solid fa-image"></i> Upload Event Image</label>
                    <input type="file" name="event_image" id="eventImageInput" class="form-control" accept="image/*" onchange="previewImage(this)">
                    <?php if ($action === 'edit' && $edit_evt['image']): ?>
                        <img src="<?php echo (strpos($edit_evt['image'], 'http') === 0) ? $edit_evt['image'] : '../assets/image/Events/' . htmlspecialchars($edit_evt['image']); ?>" id="imgPreview" class="event-img-preview">
                    <?php else: ?>
                        <img src="" id="imgPreview" class="event-img-preview" style="display:none;">
                    <?php endif; ?>
                    <p class="tag-hint">Recommended: 1200×630px, JPG/PNG. Shown on event cards and social shares.</p>
                </div>

                <!-- ── SEO / Tags ── -->
                <div class="section-label" style="margin-top:2rem;"><i class="fa-solid fa-tags"></i> SEO & Tags</div>
                <div class="form-group">
                    <label>Tags (comma-separated)</label>
                    <input type="text" name="tags" class="form-control" placeholder="e.g. programming, workshop, hackathon, AI" value="<?php echo htmlspecialchars($edit_evt['tags'] ?? ''); ?>">
                    <p class="tag-hint">Tags help with search visibility. Separate with commas. Example: <em>workshop, python, AI, 2025</em></p>
                </div>
                <div class="form-group">
                    <label>Meta Description (for SEO)</label>
                    <textarea name="meta_description" class="form-control" rows="2" maxlength="300" placeholder="Brief description shown in Google search results (max 300 chars)..."><?php echo htmlspecialchars($edit_evt['meta_description'] ?? ''); ?></textarea>
                    <p class="tag-hint">Ideal length: 150–160 characters. This appears under the event title in search engines.</p>
                </div>

                <div style="display:flex; gap:1rem; margin-top:2rem;">
                    <button type="submit" name="<?php echo $action === 'edit' ? 'edit_event' : 'add_event'; ?>" class="btn btn-primary" style="flex:1; padding:1rem;">
                        <i class="fa-solid fa-<?php echo $action === 'edit' ? 'floppy-disk' : 'rocket'; ?>"></i>
                        <?php echo $action === 'edit' ? 'Save Changes' : 'Publish Event'; ?>
                    </button>
                    <a href="events.php" class="btn" style="flex:0.3; padding:1rem; text-align:center; background:var(--border-color); color:var(--text-main); text-decoration:none;">Cancel</a>
                </div>
            </form>
        </div>

        <?php else: ?>
            <div class="alert alert-error">You do not have permission to perform this action.</div>
        <?php endif; ?>
    </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function toggleFee(type) {
            document.getElementById('feeField').classList.toggle('show', type === 'Paid');
        }
        function previewImage(input) {
            const preview = document.getElementById('imgPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
