
<?php
$current_year = date('Y');
?>

<footer class="footer">

    <div class="footer-content">

        <div class="footer-brand">

            <h2>GlobeTrek Adventures</h2>

            <p>
                Discover new destinations and create
                unforgettable travel experiences with GlobeTrek.
            </p>

        </div>

        <div class="footer-links">

            <h3>Quick Links</h3>

            <a href="index.php">Home</a>

            <a href="packages.php">Travel Packages</a>

            <a href="contact.php">Contact Us</a>

            <a href="login.php">Login</a>

        </div>

    </div>

    <div class="footer-bottom">

        <p>
            &copy; <?php echo $current_year; ?>
            GlobeTrek Adventures. All Rights Reserved.
        </p>

    </div>

</footer>

<style>
    .footer {
        background: #102a43;
        color: white;
        padding: 30px 5% 15px;
        margin-top: 40px;
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 30px;
        max-width: 1200px;
        margin: auto;
    }

    .footer-brand {
        max-width: 400px;
    }

    .footer-brand h2 {
        margin-bottom: 12px;
    }

    .footer-brand p {
        line-height: 1.6;
    }

    .footer-links {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .footer-links h3 {
        margin-bottom: 5px;
    }

    .footer-links a {
        color: white;
        text-decoration: none;
    }

    .footer-links a:hover {
        color: #5eead4;
    }

    .footer-bottom {
        text-align: center;
        border-top: 1px solid rgba(255,255,255,0.2);
        margin-top: 25px;
        padding-top: 15px;
    }

    @media (max-width: 600px) {
        .footer-content {
            flex-direction: column;
        }
    }
</style>
