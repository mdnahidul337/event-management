<?php
// Modern Footer for SCC
?>
<footer class="main-footer"
    style="background: #111827; color: #f3f4f6; padding: 5rem 5% 2rem; margin-top: auto; border-top: 1px solid #374151;">
    <div class="footer-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; max-width: 1200px; margin: 0 auto;">

        <!-- Brand Section -->
        <div class="footer-brand">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                <img src="assets/image/logo.jpg" alt="SIMT Logo"
                    style="height: 50px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                <? php ?>
                <?php echo htmlspecialchars($site_name); ?>
                <?php ?>
            </div>
            <p style="color: #9ca3af; line-height: 1.6; font-size: 0.95rem; margin-bottom: 2rem;">
                Empowering the next generation of tech leaders through collaboration, workshops, and high-impact events
                at SIMT.
            </p>
            <div class="social-links" style="display: flex; gap: 1rem;">
                <a href="#"
                    style="background: #1f2937; padding: 0.6rem; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center;"><img
                        src="assets/image/Soical-media-Icons/facebook.svg" style="width: 20px; height: 20px;"
                        alt="Facebook"></a>
                <a href="#"
                    style="background: #1f2937; padding: 0.6rem; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center;"><img
                        src="assets/image/Soical-media-Icons/twitter.svg" style="width: 20px; height: 20px;"
                        alt="Twitter"></a>
                <a href="#"
                    style="background: #1f2937; padding: 0.6rem; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center;"><img
                        src="assets/image/Soical-media-Icons/instagram-1-svgrepo-com.svg"
                        style="width: 20px; height: 20px;" alt="Instagram"></a>
                <a href="#"
                    style="background: #1f2937; padding: 0.6rem; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center;"><img
                        src="assets/image/Soical-media-Icons/whatsapp-icon-logo-svgrepo-com.svg"
                        style="width: 20px; height: 20px;" alt="WhatsApp"></a>
                <a href="#"
                    style="background: #1f2937; padding: 0.6rem; border-radius: 50%; transition: 0.3s; display: flex; align-items: center; justify-content: center;"><img
                        src="assets/image/Soical-media-Icons/youtube-icon-svgrepo-com.svg"
                        style="width: 20px; height: 20px;" alt="YouTube"></a>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="footer-links">
            <h3
                style="color: white; font-size: 1.1rem; margin-bottom: 1.5rem; position: relative; padding-bottom: 0.5rem;">
                Important Events
                <span
                    style="position: absolute; bottom: 0; left: 0; width: 30px; height: 2px; background: #4f46e5;"></span>
            </h3>
            <ul style="list-style: none;">
                <?php
                // Fetch top 3 upcoming events for quick access
                if (isset($pdo)) {
                    $stmt_ft = $pdo->query("SELECT id, title FROM events WHERE status='Upcoming' ORDER BY start_date ASC LIMIT 3");
                    $ft_evts = $stmt_ft->fetchAll();
                    foreach ($ft_evts as $fe): ?>
                        <li style="margin-bottom: 0.8rem;"><a href="join_event.php?id=<?php echo $fe['id']; ?>"
                                style="color: #9ca3af; text-decoration: none; transition: 0.3s;"><?php echo htmlspecialchars($fe['title']); ?></a>
                        </li>
                    <?php endforeach;
                }
                ?>
                <li style="margin-top: 1rem; border-top: 1px solid #1f2937; padding-top: 1rem;"><a
                        href="public_events.php" style="color: #4f46e5; text-decoration: none; font-weight: 600;">View
                        All Events →</a></li>
            </ul>
        </div>

        <!-- Quick Links -->
        <div class="footer-links">
            <h3
                style="color: white; font-size: 1.1rem; margin-bottom: 1.5rem; position: relative; padding-bottom: 0.5rem;">
                Quick Access
                <span
                    style="position: absolute; bottom: 0; left: 0; width: 30px; height: 2px; background: #4f46e5;"></span>
            </h3>
            <ul style="list-style: none;">
                <li style="margin-bottom: 0.8rem;"><a href="index.php"
                        style="color: #9ca3af; text-decoration: none; transition: 0.3s;">Home</a></li>
                <li style="margin-bottom: 0.8rem;"><a href="profile.php"
                        style="color: #9ca3af; text-decoration: none; transition: 0.3s;">My Profile</a></li>
                <li style="margin-bottom: 0.8rem;"><a href="register.php"
                        style="color: #9ca3af; text-decoration: none; transition: 0.3s;">Join Club</a></li>
                <li style="margin-bottom: 0.8rem;"><a href="login.php"
                        style="color: #9ca3af; text-decoration: none; transition: 0.3s;">Member Login</a></li>
            </ul>
        </div>

        <!-- Contact Section -->
        <div class="footer-contact">
            <h3
                style="color: white; font-size: 1.1rem; margin-bottom: 1.5rem; position: relative; padding-bottom: 0.5rem;">
                Contact Us
                <span
                    style="position: absolute; bottom: 0; left: 0; width: 30px; height: 2px; background: #4f46e5;"></span>
            </h3>
            <ul style="list-style: none; color: #9ca3af; font-size: 0.95rem;">
                <li style="margin-bottom: 1.2rem; display: flex; gap: 0.75rem;">
                    <i class="fa-solid fa-location-dot" style="color: #4f46e5; margin-top: 0.25rem;"></i>
                    <span>M/3, Section-14, Mirpur,<br>Dhaka-1216</span>
                </li>
                <li style="margin-bottom: 1.2rem; display: flex; gap: 0.75rem;">
                    <i class="fa-solid fa-phone" style="color: #4f46e5;"></i>
                    <span>01936-005818-16</span>
                </li>
                <li style="margin-bottom: 1.2rem; display: flex; gap: 0.75rem;">
                    <i class="fa-solid fa-envelope" style="color: #4f46e5;"></i>
                    <span>simt.dhaka@gmail.com</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="footer-bottom"
        style="max-width: 1200px; margin: 4rem auto 0; padding-top: 2rem; border-top: 1px solid #1f2937; text-align: center; color: #6b7280; font-size: 0.85rem;">
        <p>Copyright &copy; 2026 SIMT Compute Technology | Powered by <span
                style="color: #4f46e5; font-weight: 600;">SCC Compute Club</span></p>
    </div>
</footer>

<style>
    .social-links a:hover {
        background: #4f46e5 !important;
        transform: translateY(-3px);
    }

    .footer-links a:hover {
        color: #4f46e5 !important;
        padding-left: 5px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all payment instruction containers
    const containers = document.querySelectorAll('.payment-section, .payment-box, .payment-instructions');
    
    // Regex for BD phone numbers (starting with 01, total 11 digits)
    const phoneRegex = /\b01[3-9]\d{8}\b/g;

    containers.forEach(container => {
        // We need to walk through text nodes to avoid breaking HTML structures
        const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null, false);
        let node;
        const nodesToReplace = [];

        while (node = walker.nextNode()) {
            if (phoneRegex.test(node.nodeValue)) {
                nodesToReplace.push(node);
            }
        }

        nodesToReplace.forEach(textNode => {
            const parent = textNode.parentNode;
            const content = textNode.nodeValue;
            const fragment = document.createDocumentFragment();
            let lastIndex = 0;
            let match;

            phoneRegex.lastIndex = 0; // Reset regex
            while ((match = phoneRegex.exec(content)) !== null) {
                // Add text before match
                fragment.appendChild(document.createTextNode(content.slice(lastIndex, match.index)));
                
                // Create copyable span
                const span = document.createElement('span');
                span.className = 'copyable-number';
                span.textContent = match[0];
                span.title = 'Click to copy';
                
                const tooltip = document.createElement('span');
                tooltip.className = 'copy-tooltip';
                tooltip.textContent = 'Copied!';
                span.appendChild(tooltip);

                span.addEventListener('click', function(e) {
                    e.stopPropagation();
                    navigator.clipboard.writeText(this.textContent.replace('Copied!', '')).then(() => {
                        tooltip.classList.add('show');
                        setTimeout(() => tooltip.classList.remove('show'), 1500);
                    });
                });

                fragment.appendChild(span);
                lastIndex = phoneRegex.lastIndex;
            }
            // Add remaining text
            fragment.appendChild(document.createTextNode(content.slice(lastIndex)));
            parent.replaceChild(fragment, textNode);
        });
    });
});
</script>