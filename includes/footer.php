<?php
/**
 * Shared Footer Component
 * CSE 391: Programming for the Internet - Assignment 3
 */
?>
<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-col" style="max-width: 380px;">
                <h4>⚙️ Apex Auto Workshop</h4>
                <p style="margin-bottom: 12px;">
                    We house 5 senior certified mechanics dedicated to premium car diagnostics, powertrain rebuilds, and electrical system tuning.
                </p>
                <p style="font-size: 0.85rem; color: #64748b;">
                    Each mechanic handles a maximum of 4 active cars per day to guarantee meticulous care.
                </p>
            </div>

            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul style="list-style: none; font-size: 0.875rem; display: flex; flex-direction: column; gap: 8px;">
                    <li><a href="<?= getBaseUrl() ?>/index.php" style="color: #94a3b8;">📅 Book Appointment</a></li>
                    <li><a href="<?= getBaseUrl() ?>/status.php" style="color: #94a3b8;">🔍 Check Appointment Status</a></li>
                    <li><a href="<?= getBaseUrl() ?>/index.php#help-section" style="color: #94a3b8;">❓ Help & Policy Guidelines</a></li>
                    <li><a href="<?= getBaseUrl() ?>/admin/index.php" style="color: #94a3b8;">🛡️ Workshop Admin Login</a></li>
                    <li><a href="<?= getBaseUrl() ?>/database/setup.php" style="color: #94a3b8;">⚡ Database Auto-Setup</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Workshop Contact</h4>
                <p style="margin-bottom: 6px;">📍 42 Motorway Avenue, Auto City</p>
                <p style="margin-bottom: 6px;">📞 Hotline: +880 1711-000999</p>
                <p style="margin-bottom: 6px;">🕒 Working Hours: 8:00 AM – 6:00 PM</p>
                <p style="margin-bottom: 6px;">✉️ Email: service@apexworkshop.com</p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>
                CSE 391: Programming for the Internet &bull; Assignment 3: PHP and mySQL Project &bull; &copy; <?= date('Y') ?> Apex Auto Workshop.
            </p>
        </div>
    </div>
</footer>

</body>
</html>

