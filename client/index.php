<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/png" href="assets/logo/sq.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/landing.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <title>SmartQ — Modern Student ID Validation & Queue System</title>
</head>

<body>

  <!-- TOP BAR -->
  <nav class="top-bar" id="navbar">
    <div class="container">
      <div class="logo">
        <img src="assets/logo/sq-blue-d.png" alt="SmartQ Logo">
      </div>

      <div class="nav-links">
        <a href="#home">Home</a>
        <a href="#about">About</a>
        <a href="#team">Team</a>
        <a href="#contact">Contact</a>
      </div>

      <div class="nav-actions">
        <a href="pages/login.php" class="btn-login">Login</a>
        <a href="pages/signup.php" class="btn-signup">Get Started</a>
      </div>

      <!-- Mobile Toggle -->
      <div class="mobile-toggle" id="mobile-menu-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </nav>

  <!-- HERO SECTION -->
  <section id="home" class="hero-section">
    <div class="container">
      <div class="hero-content">
        <span class="badge">Next-Gen Queuing Solution</span>
        <h1>Smart Queues for Smart Campuses.</h1>
        <p>Revolutionize how your institution handles student ID validation. No more long lines, just seamless,
          efficient, and transparent queuing.</p>
        <div class="hero-btns">
          <a href="pages/signup.php" class="btn-main btn-primary">
            Start Your Journey <i class="fas fa-arrow-right"></i>
          </a>
          <a href="#about" class="btn-main btn-secondary">Explore Features</a>
          <a href="pages/login.php" class="btn-main btn-login-hero">
            <i class="fas fa-user-circle"></i> Login to Account
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ABOUT SECTION -->
  <section id="about">
    <div class="container">
      <div class="about-grid">
        <div class="about-image">
          <img src="assets/logo/sq-blue-d.png" alt="SmartQ System Illustration">
          <div class="floating-card">
            <i class="fas fa-bolt"></i>
            <div>
              <strong>99.9%</strong>
              <span>Uptime recorded</span>
            </div>
          </div>
        </div>

        <div class="about-text">
          <span class="badge">Our Mission</span>
          <h2>Transforming Campus Logistics with Innovation.</h2>
          <p>SmartQ was born out of the need to solve the perennial problem of campus congestion. By merging real-time
            data with intuitive user interfaces, we've created a system that respects your time.</p>

          <div class="about-features">
            <div class="feature-item">
              <div class="icon"><i class="fas fa-shield-halved"></i></div>
              <div>
                <h4>Secure Validation</h4>
                <p>Admin validation approval for student ID and event.</p>
              </div>
            </div>
            <div class="feature-item">
              <div class="icon"><i class="fas fa-clock"></i></div>
              <div>
                <h4>Smart Estimations</h4>
                <p>Manage schedules seamlessly with just a few clicks.</p>
              </div>
            </div>
            <div class="feature-item">
              <div class="icon"><i class="fas fa-paper-plane"></i></div>
              <div>
                <h4>Instant Alerts</h4>
                <p>Get notified the moment your number is called, anywhere.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TEAM SECTION -->
  <section id="team">
    <div class="container">
      <div class="team-section">
        <div class="section-header" style="margin-bottom: 40px;">
          <h2>Meet the Team</h2>
          <p>The innovative minds behind the SmartQ system.</p>
        </div>

        <div class="team-grid">
          <!-- Team Member 1 -->
          <div class="team-card">
            <div class="team-img-large">
              <img src="assets/img/althea.jpg" alt="Althea Hassel Daing">
            </div>
            <div class="team-info">
              <h4>Althea Hassel Daing</h4>
              <span class="role">UI/UX Designer</span>
              <p class="description">Designs clean, user‑friendly interfaces with a focus on detail.</p>
              <div class="team-socials">
                <div class="social-dot"></div>
                <div class="social-dot"></div>
                <div class="social-dot"></div>
              </div>
            </div>
          </div>

          <!-- Team Member 2 -->
          <div class="team-card">
            <div class="team-img-large">
              <img src="assets/img/bernbern.jpg" alt="Alejandra Bernasol">
            </div>
            <div class="team-info">
              <h4>Alejandra Bernasol</h4>
              <span class="role">System Analyst</span>
              <p class="description">Analyzes systems and ensures efficient workflow design. </p>
              <div class="team-socials">
                <div class="social-dot"></div>
                <div class="social-dot"></div>
                <div class="social-dot"></div>
              </div>
            </div>
          </div>

          <!-- Team Member 3 -->
          <div class="team-card">
            <div class="team-img-large">
              <img src="assets/img/ged.jpg" alt="Ged Shareef Diayon">
            </div>
            <div class="team-info">
              <h4>Ged Shareef Diayon</h4>
              <span class="role">Hustler</span>
              <p class="description">Drives project success through strong teamwork and effective coordination.</p>
              <div class="team-socials">
                <div class="social-dot"></div>
                <div class="social-dot"></div>
                <div class="social-dot"></div>
              </div>
            </div>
          </div>

          <!-- Team Member 4 -->
          <div class="team-card">
            <div class="team-img-large">
              <img src="assets/img/wen.jpg" alt="Owen Jerusalem">
            </div>
            <div class="team-info">
              <h4>Owen Jerusalem</h4>
              <span class="role">Project Lead / Developer</span>
              <p class="description">Leads development with clean, efficient code and ensures system reliability.</p>
              <div class="team-socials">
                <div class="social-dot"></div>
                <div class="social-dot"></div>
                <div class="social-dot"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CONTACT SECTION -->
  <section id="contact">
    <div class="container">
      <div class="contact-container">
        <div class="contact-info">
          <h2>Get in Touch.</h2>
          <p>Have questions about SmartQ or need technical support? We're here to help you optimize your institution's
            flow.</p>

          <div class="contact-list">
            <div class="contact-item">
              <div class="icon"><i class="fas fa-envelope"></i></div>
              <div>
                <h4>Email Us</h4>
                <p>smartq.aago@gmail.com</p>
              </div>
            </div>
            <div class="contact-item">
              <div class="icon"><i class="fas fa-location-dot"></i></div>
              <div>
                <h4>Location</h4>
                <p>Bukidnon State University, Malaybalay City</p>
              </div>
            </div>
          </div>
        </div>

        <div class="contact-form">
          <form action="#">
            <div class="form-group">
              <input type="text" placeholder="Full Name" required>
            </div>
            <div class="form-group">
              <input type="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
              <textarea rows="4" placeholder="Your Message" required></textarea>
            </div>
            <button type="submit" class="btn-submit">Send Message</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <footer>
    <div class="container">
      <p>&copy; 2024 SmartQ System. Built with excellence for BukSU.</p>
    </div>
  </footer>

  <script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
      const nav = document.getElementById('navbar');
      if (window.scrollY > 50) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    });

    // Mobile menu toggle
    const toggle = document.getElementById('mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    toggle.addEventListener('click', () => {
      toggle.classList.toggle('active');
      navLinks.classList.toggle('active');
      document.body.classList.toggle('no-scroll');
    });

    // Close menu when clicking links
    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => {
        toggle.classList.remove('active');
        navLinks.classList.remove('active');
        document.body.classList.remove('no-scroll');
      });
    });

    // Smooth scroll for nav links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });
  </script>

</body>

</html>