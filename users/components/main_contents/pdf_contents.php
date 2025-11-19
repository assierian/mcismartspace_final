<div id="pdf-content">
    <?php if ($status == 'approved'): ?>
        <div class="watermark approved">APPROVED</div>
    <?php elseif ($status == 'rejected'): ?>
        <div class="watermark rejected">REJECTED</div>
    <?php endif; ?>

    <div class="header">
        <img src="../public/assets/logo.webp" alt="Meycauayan College Logo" class="logo">
        <h1>Meycauayan College, Inc.</h1>
        <div class="subtitle">City of Meycauayan Bulacan</div>
        <div class="office-title">Deans' Office</div>
        <div class="form-title">Requisition Form</div>
    </div>

    <div class="date-line">
        <span>Date: </span>
        <span><?php echo $currentDate; ?></span>
    </div>

    <div class="addressee">
        THE VICE PRESIDENT FOR ACADEMIC AFFAIR<br>
        Meycauayan College
    </div>

    <div class="thru-line">
        Thru: Deans' Office
    </div>

    <div class="subject-line">
        Subject: Confirmation of Room Request
    </div>

    <div class="salutation">
        Madam,
    </div>

    <div class="content">
        This is to confirm that <?php echo htmlspecialchars($userData['FirstName'] . ' ' . $userData['LastName']); ?> from
        <?php 
        if ($userRole == 'Student') {
            echo htmlspecialchars($userData['Department'] . ' - ' . $userData['YearSection']);
        } else {
            echo htmlspecialchars($userData['Department']);
        }
        ?>
        has formally requested the use of a room for an upcoming activity.
    </div>

    <div class="form-fields">
        <div class="form-field">
            <div class="form-label">Requested Room:</div>
            <div class="form-value"><?php echo htmlspecialchars($roomName . ', ' . $buildingName); ?></div>
        </div>
        <div class="form-field">
            <div class="form-label">Activity:</div>
            <div class="form-value"><?php echo htmlspecialchars($activityName); ?></div>
        </div>
        <div class="form-field">
            <div class="form-label">Purpose:</div>
            <div class="form-value"><?php echo htmlspecialchars($purpose); ?></div>
        </div>
        <div class="form-field">
            <div class="form-label">Date/Time of Activity:</div>
            <div class="form-value"><?php echo htmlspecialchars($reservationDate . ', ' . $startTime . ' - ' . $endTime); ?></div>
        </div>
        <?php if ($userRole == 'Student'): ?>
        <div class="form-field">
            <div class="form-label">Program/Section:</div>
            <div class="form-value"><?php echo htmlspecialchars($userData['YearSection']); ?></div>
        </div>
        <?php endif; ?>
        <div class="form-field">
            <div class="form-label">Department:</div>
            <div class="form-value"><?php echo htmlspecialchars($userData['Department']); ?></div>
        </div>
        <div class="form-field">
            <div class="form-label">No. of expected participants:</div>
            <div class="form-value"><?php echo htmlspecialchars($participants); ?></div>
        </div>
    </div>

    <div class="agreement">
        We agree to follow the terms and conditions for using the assigned room/s at Meycauayan College.
    </div>

    <div class="signatures">
        <div class="signature-row">
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">NAME & SIGNATURE</div>
                <div>Requested by:</div>
            </div>
            <div class="signature">
                <div class="signature-line"></div>
                <div class="signature-label">NAME & SIGNATURE</div>
                <div>Assisted by:</div>
            </div>
        </div>

        <div class="noted-signature">
            <div class="signature-line"></div>
            <div class="signature-label">NAME & SIGNATURE</div>
            <div>Noted by:</div>
        </div>
    </div>
</div>