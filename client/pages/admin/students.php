<?php
session_start();
if (!isset($_SESSION['admin'])) {
	header('Location: ../login.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" href="../../assets/logo/sq.png">

	<!-- Tell the component loader where to find components -->
	<meta name="component-base" content="../../components/">

	<!-- Stylesheets -->
	<link rel="stylesheet" href="../../assets/css/main.css">
	<link rel="stylesheet" href="../../assets/css/components/components.css">
	<link rel="stylesheet" href="../../assets/css/admin/students.css">

	<title>SmartQ | Student Management</title>
</head>

<body>

	<div class="admin-layout">
		<!-- Sidebar -->
		<div data-component="sidebar" data-props='{"active":"students"}'></div>

		<div class="admin-main">
			<!-- Topbar -->
			<div data-component="topbar"
				data-props='{"title":"Student Directory", "description":"Manage and validate student identities."}'></div>

			<main class="admin-content">
				<div class="students-container">

					<!-- ── Toolbar ── -->
					<div class="students-toolbar">
						<div class="search-wrapper">
							<span class="search-icon">
								<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
									<circle cx="11" cy="11" r="8"></circle>
									<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
								</svg>
							</span>
							<input type="text" class="search-input" placeholder="Search by ID, First Name or Last Name...">
						</div>
						<div class="filter-group">
							<select class="filter-select" id="year-filter">
								<option value="">All Years</option>
								<option value="1st Year">1st Year</option>
								<option value="2nd Year">2nd Year</option>
								<option value="3rd Year">3rd Year</option>
								<option value="4th Year">4th Year</option>
							</select>
							<select class="filter-select" id="college-filter">
								<option value="">All Colleges</option>
								<option value="COT">COT (Technology)</option>
								<option value="CAS">CAS (Arts & Sciences)</option>
								<option value="CON">CON (Nursing)</option>
								<option value="COB">COB (Business)</option>
								<option value="COE">COE (Education)</option>
								<option value="CPAG">CPAG (Public Admin)</option>
							</select>
							<select class="filter-select" id="status-filter">
								<option value="">All Status</option>
								<option value="validated">Validated</option>
								<option value="not validated">Not Validated</option>
								<option value="pending">Pending</option>
							</select>

						</div>
					</div>

					<!-- ── Table ── -->
					<div class="students-table-container">
						<table class="students-table">
							<thead>
								<tr>
									<th>Student ID</th>
									<th>First Name</th>
									<th>Last Name</th>
									<th>Email</th>
									<th>Year</th>
									<th>College</th>
									<th>Status</th>
									<th style="text-align: right;">Actions</th>
								</tr>
							</thead>

							<tbody>
								<?php
								require_once "../../../server/config/database.php";

								$database = new Database();
								$db = $database->getConnection();

								$sql = "SELECT s.*, c.college_name, vs.status_name 
                        FROM students s 
                        LEFT JOIN colleges c ON s.college_id = c.college_id 
                        LEFT JOIN validation_status vs ON s.status_id = vs.status_id";

								$stmt = $db->prepare($sql);
								$stmt->execute();

								if ($stmt->rowCount() > 0) {
									while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
										$status_class = strtolower(str_replace(' ', '-', $row['status_name']));
										echo "<tr>";
										echo "<td class='student-id-cell'>" . htmlspecialchars($row['student_id']) . "</td>";
										echo "<td class='student-name-cell'>" . htmlspecialchars($row['first_name']) . "</td>";
										echo "<td class='student-name-cell'>" . htmlspecialchars($row['last_name']) . "</td>";
										echo "<td class='email-cell'>" . htmlspecialchars($row['email']) . "</td>";
										$year_val = $row['yearlvl'] ?? 'N/A';
										$year_labels = [
											1 => '1st Year',
											2 => '2nd Year',
											3 => '3rd Year',
											4 => '4th Year'
										];
										$display_year = isset($year_labels[$year_val]) ? $year_labels[$year_val] : ($year_val != 'N/A' ? $year_val . 'th Year' : 'N/A');

										echo "<td class='year-cell'><span class='year-badge-small'>" . htmlspecialchars($display_year) . "</span></td>";
										$college_abbr = $row['college_name'] ?? 'N/A';
										$college_colors = [
											'COT' => ['bg' => '#fff7ed', 'text' => '#ff7d04'],
											'CON' => ['bg' => '#fdf2f8', 'text' => '#ec57ee'],
											'COB' => ['bg' => '#fffbeb', 'text' => '#fac800'],
											'COE' => ['bg' => '#eff6ff', 'text' => '#1c5adf'],
											'CPAG' => ['bg' => '#f0fdfa', 'text' => '#23c7c7'],
											'CAS' => ['bg' => '#f0fdf4', 'text' => '#10b981'],
										];
										$colors = $college_colors[$college_abbr] ?? ['bg' => '#f1f5f9', 'text' => '#64748b'];

										echo "<td class='college-cell'><span class='college-badge-small' style='background:{$colors['bg']}; color:{$colors['text']}; border-color:{$colors['text']}20;'>" . htmlspecialchars($college_abbr) . "</span></td>";
										echo "<td class='status-cell'><span class='status-badge badge-" . $status_class . "'>" . htmlspecialchars($row['status_name'] ?? 'Pending') . "</span></td>";
										echo "<td>
                            <div class='action-buttons' style='justify-content: flex-end;'>";

										if (trim(strtolower($row['status_name'])) === 'not validated') {
											// Show Email button
											echo "<button class='btn-action btn-email' title='Send Reminder Email'>
                              <svg width='14' height='14' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                <path d='M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z'></path>
                                <polyline points='22,6 12,13 2,6'></polyline>
                              </svg>
                            </button>";
										} else if (trim(strtolower($row['status_name'])) === 'pending') {
											// Show Approve/Reject
											$sid = htmlspecialchars($row['student_id']);
											echo "<button class='btn-action btn-approve' title='Approve Validation' data-student-id='{$sid}'>
                              <svg width='14' height='14' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                <polyline points='20 6 9 17 4 12'></polyline>
                              </svg>
                            </button>
                            <button class='btn-action btn-reject' title='Reject / Cancel Validation' data-student-id='{$sid}'>
                              <svg width='14' height='14' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                <line x1='18' y1='6' x2='6' y2='18'></line>
                                <line x1='6' y1='6' x2='18' y2='18'></line>
                              </svg>
                            </button>";
										} else {
											$sid = htmlspecialchars($row['student_id']);
											echo "<button class='btn-action btn-view' title='View Details' data-student-id='{$sid}'>
                              <svg width='14' height='14' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'>
                                <path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'></path>
                                <circle cx='12' cy='12' r='3'></circle>
                              </svg>
                            </button>";
										}

										echo "  </div>
                          </td>";
										echo "</tr>";
									}
								} else {
									echo "<tr><td colspan='8'>No students found</td></tr>";
								}
								?>
							</tbody>
						</table>
					</div>

				</div>
			</main>

			<div data-component="footer"></div>
		</div>
	</div>

	<!-- ── Action Confirmation Modal ── -->
	<div id="action-modal" class="modal-overlay">
		<div id="modal-backdrop" class="modal-backdrop"></div>
		<div class="modal-card">
			<div id="modal-icon" class="modal-icon"></div>
			<h3 id="modal-title" class="modal-title"></h3>
			<p id="modal-desc" class="modal-desc"></p>
			<div class="modal-actions">
				<button id="modal-cancel-btn" class="modal-btn-cancel">Cancel</button>
				<button id="modal-confirm-btn" class="modal-btn-confirm">Confirm</button>
			</div>
		</div>
	</div>

	<!-- ── Student Details Modal ── -->
	<div id="details-modal" class="modal-overlay">
		<div id="details-backdrop" class="modal-backdrop"></div>
		<div class="details-card">
			<div class="details-header">
				<button id="details-close-btn" class="details-close">
					<i class="fas fa-times"></i>
				</button>
				<div class="details-user-info">
					<div class="details-avatar">
						<i class="fas fa-user"></i>
					</div>
					<div>
						<div id="details-name" class="details-name-text"></div>
						<div id="details-id" class="details-id-text"></div>
					</div>
				</div>
				<div class="details-badge-container">
					<span id="validated-by-badge" class="validated-badge">
					</span>
				</div>
			</div>

			<div class="details-body">
				<p class="detail-section-label">Student Information</p>
				<div id="details-info-grid" class="details-grid">
				</div>

				<hr class="details-divider">

				<p class="detail-section-label">Validation Details</p>
				<div id="details-val-grid" class="details-grid"></div>
			</div>

			<div id="details-loader" class="details-loader-overlay">
				<div class="spinner-small details-spinner"></div>
			</div>
		</div>
	</div>


	<!-- Scripts -->
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="../../scripts/component-loader.js"></script>

	<script>
		SmartQ.onLoad('sidebar', function ($el) {
			$(document).on('click', '#sidebar-toggle', function () {
				$('#sidebar').toggleClass('open');
			});
		});

		// Multi-Filter Logic
		$(document).ready(function () {
			function filterTable() {
				const searchText = $('.search-input').val().toLowerCase();
				const yearFilter = $('#year-filter').val().toLowerCase();
				const collegeFilter = $('#college-filter').val().toLowerCase();
				const statusFilter = $('#status-filter').val().toLowerCase();

				let visibleCount = 0;

				$(".students-table tbody tr:not(.no-results-row)").each(function () {
					const rowText = $(this).text().toLowerCase();
					const rowYear = $(this).find('.year-cell').text().toLowerCase().trim();
					const rowCollege = $(this).find('.college-cell').text().toLowerCase().trim();
					const rowStatus = $(this).find('.status-cell').text().toLowerCase().trim();

					const matchesSearch = rowText.indexOf(searchText) > -1;
					const matchesYear = yearFilter === "" || rowYear === yearFilter;
					const matchesCollege = collegeFilter === "" || rowCollege.indexOf(collegeFilter.toLowerCase()) > -1;
					const matchesStatus = statusFilter === "" || rowStatus === statusFilter;

					const isVisible = matchesSearch && matchesYear && matchesCollege && matchesStatus;
					$(this).toggle(isVisible);

					if (isVisible) visibleCount++;
				});

				// Show/Hide "No results" row
				if ($('.no-results-row').length === 0) {
					$('.students-table tbody').append('<tr class="no-results-row"><td colspan="8" style="text-align:center; padding: 40px; color: var(--text-muted);">No students matching your filters found.</td></tr>');
				}
				$('.no-results-row').toggle(visibleCount === 0);
			}

			$('.search-input').on('keyup', filterTable);
			$('.filter-select').on('change', filterTable);

			// ── Validation Status Modal ──────────────────────────────────────
			const $modal = $('#action-modal');
			const $modalIcon = $('#modal-icon');
			const $modalTitle = $('#modal-title');
			const $modalDesc = $('#modal-desc');
			const $confirmBtn = $('#modal-confirm-btn');
			const $cancelBtn = $('#modal-cancel-btn');

			let pendingAction = null; // { studentId, action, $row }

			function openModal(config) {
				$modalIcon.css('background', config.iconBg).html(config.iconSvg);
				$modalTitle.text(config.title);
				$modalDesc.text(config.desc);
				$confirmBtn.css('background', config.confirmColor);
				$modal.css('display', 'flex').hide().fadeIn(200);
			}

			function closeModal() {
				$modal.fadeOut(150);
				pendingAction = null;
			}

			$('#modal-backdrop, #modal-cancel-btn').on('click', closeModal);

			// Approve button
			$(document).on('click', '.btn-approve', function () {
				const $row = $(this).closest('tr');
				const id = $(this).data('student-id') || $row.find('.student-id-cell').text().trim();
				pendingAction = { studentId: id, action: 'approve', $row };
				openModal({
					iconBg: 'rgba(34,197,94,0.15)',
					iconSvg: '<svg width="24" height="24" fill="none" stroke="#22c55e" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>',
					title: 'Approve Validation?',
					desc: `Are you sure you want to approve ID validation for Student ${id}? Their status will be set to Validated.`,
					confirmColor: '#22c55e'
				});
			});

			// Reject button
			$(document).on('click', '.btn-reject', function () {
				const $row = $(this).closest('tr');
				const id = $(this).data('student-id') || $row.find('.student-id-cell').text().trim();
				pendingAction = { studentId: id, action: 'reject', $row };
				openModal({
					iconBg: 'rgba(239,68,68,0.12)',
					iconSvg: '<svg width="24" height="24" fill="none" stroke="#ef4444" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
					title: 'Reject / Cancel Validation?',
					desc: `Are you sure you want to reject the validation request for Student ${id}? Their status will be reset to Not Validated.`,
					confirmColor: '#ef4444'
				});
			});

			// Confirm action → AJAX
			$confirmBtn.on('click', function () {
				if (!pendingAction) return;

				const { studentId, action, $row } = pendingAction;
				$confirmBtn.prop('disabled', true).text('Processing...');

				$.ajax({
					url: '../../../server/api/students/update_status.php',
					method: 'POST',
					data: { student_id: studentId, action: action },
					dataType: 'json',
					success: function (res) {
						if (res.success) {
							// Update the status badge in-place
							const isApproved = (action === 'approve');
							const newStatus = isApproved ? 'Validated' : 'Not Validated';
							const newClass = isApproved ? 'validated' : 'not-validated';
							const $statusBadge = $row.find('.status-badge');
							$statusBadge
								.removeClass()
								.addClass('status-badge badge-' + newClass)
								.text(newStatus);

							// Rebuild the action buttons for the new state
							const $actionDiv = $row.find('.action-buttons');
							if (isApproved) {
								// Validated → View only
								$actionDiv.html(
									`<button class="btn-action" title="View Details">
										<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
											<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
											<circle cx="12" cy="12" r="3"></circle>
										</svg>
									</button>`
								);
							} else {
								// Not Validated → Email reminder
								$actionDiv.html(
									`<button class="btn-action btn-email" title="Send Reminder Email">
										<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
											<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
											<polyline points="22,6 12,13 2,6"></polyline>
										</svg>
									</button>`
								);
							}

							closeModal();
						} else {
							alert('Error: ' + res.message);
						}
					},
					error: function () {
						alert('Failed to connect to the server.');
					},
					complete: function () {
						$confirmBtn.prop('disabled', false).text('Confirm');
					}
				});
			});

			$(document).on('click', '.btn-email', function () {
				const $btn = $(this);
				const id = $btn.closest('tr').find('.student-id-cell').text().trim();

				if ($btn.hasClass('loading')) return;

				$btn.addClass('loading').prop('disabled', true);
				const originalHtml = $btn.html();
				$btn.html('<span class="spinner-small"></span>');

				$.ajax({
					url: '../../../server/api/students/send_reminder.php',
					method: 'POST',
					data: { student_id: id },
					dataType: 'json',
					success: function (response) {
						if (response.success) {
							alert('Success: ' + response.message);
						} else {
							alert('Error: ' + response.message);
						}
					},
					error: function () {
						alert('Failed to connect to the server.');
					},
					complete: function () {
						$btn.removeClass('loading').prop('disabled', false).html(originalHtml);
					}
				});
			});

			// ── Student Details Modal ────────────────────────────────
			const $detailsModal = $('#details-modal');
			const $detailsLoader = $('#details-loader');

			function detailField(label, value) {
				return `<div class="detail-field">
					<div class="detail-field-label">${label}</div>
					<div class="detail-field-value">${value || '<span style="color:var(--text-muted,#aaa);">N/A</span>'}</div>
				</div>`;
			}

			function openDetailsModal(studentId) {
				$detailsModal.css('display', 'flex');
				$detailsLoader.css('display', 'flex');
				$('#details-name, #details-id').text('');
				$('#details-info-grid, #details-val-grid').empty();

				$.ajax({
					url: '../../../server/api/students/get_student_details.php',
					method: 'GET',
					data: { student_id: studentId },
					dataType: 'json',
					success: function (res) {
						if (!res.success) { alert('Error: ' + res.message); closeDetailsModal(); return; }
						const s = res.student;

						// Header
						$('#details-name').text(s.first_name + ' ' + s.last_name);
						$('#details-id').text('Student ID: ' + s.student_id);

						// Student info grid
						const yearLabels = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year' };
						$('#details-info-grid').html(
							detailField('First Name', s.first_name) +
							detailField('Last Name', s.last_name) +
							detailField('Email', `<a href="mailto:${s.email}" style="color:#2563eb;text-decoration:none;">${s.email}</a>`) +
							detailField('Year Level', s.year_display) +
							detailField('College', s.college_name)
						);

						// Validation details grid
						$('#details-val-grid').html(
							detailField('Status', `<span style="color:#22c55e;font-weight:700;">${s.status_name}</span>`) +
							detailField('Validated By', s.validated_by) +
							detailField('Date Validated', s.validated_at_formatted
								? `<span style="color:#2563eb;font-weight:500;">${s.validated_at_formatted}</span>`
								: null)
						);
						// Update admin name badge in header
						$('#validated-by-badge').text(s.validated_by ? `Validated By: ${s.validated_by}` : '').css('display', s.validated_by ? 'inline-flex' : 'none');
					},
					error: function () { alert('Could not load student details.'); closeDetailsModal(); },
					complete: function () { $detailsLoader.css('display', 'none'); }
				});
			}

			function closeDetailsModal() {
				$detailsModal.fadeOut(150);
			}

			$('#details-backdrop, #details-close-btn').on('click', closeDetailsModal);

			$(document).on('click', '.btn-view', function () {
				const id = $(this).data('student-id') || $(this).closest('tr').find('.student-id-cell').text().trim();
				openDetailsModal(id);
			});

		});
	</script>

</body>

</html>