<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASKI Alumni Career Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="css/landing_page.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .large-text p {
            font-weight: bold;
        }
        .small-text p {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Professional Job-Seeking Background Design -->
     <!-- Add this right after the opening <body> tag -->
    <div class="background-image">
        <img src="img/askibgbg.png" alt="Background Image" class="bg-image">
    </div>
    <div class="career-background">
        <div class="bg-grid"></div>
        <div class="career-path"></div>
        <div class="city-skyline"></div>
        
        <!-- Job opportunity bubbles -->
        <div class="job-bubble">
            <i class="ri-briefcase-line job-icon"></i>
        </div>
        <div class="job-bubble">
            <i class="ri-building-4-line job-icon"></i>
        </div>
        <div class="job-bubble">
            <i class="ri-user-search-line job-icon"></i>
        </div>
        <div class="job-bubble">
            <i class="ri-line-chart-line job-icon"></i>
        </div>
        
        <!-- Career ladder visual -->
        <div class="career-ladder">
            <div class="ladder-rung"></div>
            <div class="ladder-rung"></div>
            <div class="ladder-rung"></div>
            <div class="ladder-rung"></div>
            <div class="ladder-rung"></div>
        </div>
        
        <!-- Document/resume floating elements -->
        <div class="document"></div>
        <div class="document"></div>
        <div class="document"></div>
        
        <!-- Career growth chart -->
        <div class="growth-chart">
            <div class="chart-line"></div>
            <div class="chart-bar"></div>
            <div class="chart-bar"></div>
            <div class="chart-bar"></div>
            <div class="chart-bar"></div>
            <div class="chart-bar"></div>
        </div>
        
        <!-- Career arrows -->
        <div class="career-arrow"></div>
        <div class="career-arrow"></div>
        <div class="career-arrow"></div>
        
        <!-- Network connections will be added via JavaScript -->
        <div id="network-container" class="network-container"></div>
    </div>

    <nav class="navbar">
        <div class="brand" id="homeLink">
            <div class="logo">
                <img src="img/aski_logo.jpg" alt="ASKI Logo" class="logo-img">
            </div>
            <div class="app-name">
                <h1 class="appname">ASKI</h1>
                <h1 class="appname2">ALUMNI JOB FINDER</h1>
            </div>
        </div>
        
        <div class="nav-links" id="navLinks">
            <a class="panel-trigger" data-panel="history">History</a>
            <a class="panel-trigger" data-panel="contacts">Contacts</a>
            <a class="panel-trigger" data-panel="about">About</a>
            <a class="panel-trigger" data-panel="resources">Resources</a>
            <a class="panel-trigger" data-panel="faqs">FAQs</a>
        </div>
        
        <div class="auth-buttons">
            <button class="sign-up" onclick="window.location.href='registration.php'">Sign Up</button>
            <button class="sign-in" onclick="window.location.href='login.php'">Sign In</button>
        </div>
        
        <button class="menu-toggle" id="menuToggle">
            <i class="ri-menu-line"></i>
        </button>
    </nav>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a class="mobile-nav-link panel-trigger" data-panel="history">History</a>
        <a class="mobile-nav-link panel-trigger" data-panel="contacts">Contacts</a>
        <a class="mobile-nav-link panel-trigger" data-panel="about">About</a>
        <a class="mobile-nav-link panel-trigger" data-panel="resources">Resources</a>
        <a class="mobile-nav-link panel-trigger" data-panel="faqs">FAQs</a>
        <div class="mobile-auth-buttons">
            <button class="sign-up" onclick="window.location.href='registration.php'">Register</button>
            <button class="sign-in" onclick="window.location.href='login.php'">Alumni Login</button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="content">
            <div class="large-text">
                <p><strong>ASKI</strong></p>
            </div>    
            <div class="small-text">
                <p><strong>ASKI Alumni Job Finder</strong></p>        
            </div>
            <div class="intro-text">
                <p>Welcome to the ASKI Alumni Job Finder - your gateway to exciting job opportunities, career resources,
                and professional networking. Connect with employers seeking ASKI graduates and take the next step in
                your professional journey.</p>
            </div>
            <button class="find-jobs-btn" onclick="window.location.href='registration.php'">
                <i class="ri-briefcase-line"></i> Find Jobs Now
            </button>
        </div>
    </div>
    
    <!-- History Panel with Core Values, Mission, Vision -->
    <div id="historyPanel" class="content-panel">
        <div class="return-button-container">
            <button class="return-button" aria-label="Return to home page">
                <i class="ri-arrow-left-line"></i> <span>Return</span>
            </button>
            <button class="close-panel" aria-label="Close panel">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <h2>Our History</h2>
        <p>History, Overview and Accreditation</p>
        <div class="history-timeline">
            <div class="timeline-item">
                <div class="timeline-year">2010</div>
                <div class="timeline-content">
                    ASKI Skills and Knowledge Institute, Inc. or ASKI-SKI, was established as the training arm of ASKI Group of Companies, Inc. for the staff, members/clienteles and other partner organizations of ASKI Microfinance (ASKI MFI). It started as a small training institution attached to ASKI MFI in Cabanatuan City. 

                    It was on June 11, 2010 when the Securities and Exchange Commission (SEC) acknowledged ASKI-SKI as an independent institution, making it the 5th Organization under the ASKI Group. Initially, the institute prioritized the training of its staff, members, and other partner organizations. This was made through the accreditations from the Technical Education and Skills Development Authority (TESDA) and the Cooperative Development Authority (CDA).
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-year">2015</div>
                <div class="timeline-content">
                    The intention to reach out and prepare financially-challenged students for employment lead the institute to the Department of Education. On the same year, permit to offer the Technical Vocational (TechVoc) Strand under the Senior High School program was received for 2 tracks such as Home Economics (Housekeeping, Bread and Pastry Services, and Cookery) and Information and Communications Technology (Programming Java and Programming .NET Technology). These new programs contributed to ASKI’s endeavor in expanding its capabilities as an academic institution. We produced 73 pioneer students under this program.
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-year">2017</div>
                <div class="timeline-content">
                    Accreditation of Accountancy, Business and Management (ABM) track to accommodate students who want to pursue higher education and to undergo a quality work exposure in any of the 10 business units of ASKI.
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-year">2019</div>
                <div class="timeline-content">
                    The General Academic Subject (GAS) track was offered along with the launch of Junior High School Program’s Grade 7 & 8.
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-year">2020</div>
                <div class="timeline-content">
                    Accreditation of Humanities, Math and Social Science (HUMMSS) and Science, Technology, Engineering and Math (STEM) tracks and offering of Grade 9 and 10. The number of students has grown from 73 to 382.
                </div>
            </div>
        </div>
        
        <h3>Core Values</h3
        <ul class="core-values-list">
            <strong>Transformation</strong> 
            <strong>Discipline</strong> 
            <strong>Excellence</strong>
        
        <h3>Mission & Vision</h3>
        <div class="mission-vision-container">
            <div class="mission-box">
                <h4>Our Mission</h4>
                <p>To promote socio-economic transformation through family- oriented educational system anchored on Christian principles.</p>
            </div>
            <div class="vision-box">
                <h4>Our Vision</h4>
                <p>A quality educational system aimed to develop family-oriented, competitive, and empowered learners.</p>
            </div>
        </div>
    </div>
    
    <!-- Content Panels -->
    <div id="contactsPanel" class="content-panel">
        <div class="return-button-container">
            <button class="return-button" aria-label="Return to home page">
                <i class="ri-arrow-left-line"></i> <span>Return</span>
            </button>
            <button class="close-panel" aria-label="Close panel">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <h2>Contact Us</h2>
        <p>Have questions about the ASKI Alumni? Reach out to our career services team.</p>
        <p><strong>Career Services Office:</strong><br>
        Email: askiassessmentcenter@gmail.com<br>
        Phone: (+63) 997 698 6046<br>
        Address: Purok 1, Barangay Sampaloc, Talavera, Nueva Ecija</p>
        <p><strong>Office Hours:</strong><br>
        Monday to Friday: 8:00 AM - 5:00 PM<br>
        Saturday and Sunday: Closed</p>
    </div>
    
    <div id="aboutPanel" class="content-panel">
        <div class="return-button-container">
            <button class="return-button" aria-label="Return to home page">
                <i class="ri-arrow-left-line"></i> <span>Return</span>
            </button>
            <button class="close-panel" aria-label="Close panel">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <h2>About Us</h2>
        <p>The ASKI Alumni Job Finder connects graduates with employment opportunities and career development resources.</p>
        <p>Our mission is to bridge the gap between ASKI graduates and employers, providing a platform for career growth and professional networking.</p>
        <h3>Services We Offer:</h3>
        <ul>
            <li>Job matching with employers seeking ASKI graduates</li>
            <li>Resume review</li>
            <li>Networking events with industry professionals</li>
        </ul>
    </div>
    
    <div id="resourcesPanel" class="content-panel">
        <div class="return-button-container">
            <button class="return-button" aria-label="Return to home page">
                <i class="ri-arrow-left-line"></i> <span>Return</span>
            </button>
            <button class="close-panel" aria-label="Close panel">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <h2>Career Resources</h2>
        <p>Access valuable resources to enhance your job search and career development.</p>
        <h3>Career Guides:</h3>
        <p>Explore career path guides for different fields and industries.</p>
        <h3>Salary Information:</h3>
        <p>Research average salaries for different positions in various locations.</p>
    </div>
    
    <div id="faqsPanel" class="content-panel">
        <div class="return-button-container">
            <button class="return-button" aria-label="Return to home page">
                <i class="ri-arrow-left-line"></i> <span>Return</span>
            </button>
            <button class="close-panel" aria-label="Close panel">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <h2>Frequently Asked Questions</h2>
        <p>Find answers to common questions about the ASKI Alumni Job Finder.</p>
        
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">
                    How do I create an account? <i class="ri-arrow-down-s-line faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    To create an account, click on the "Register" button in the top right corner of the page. Fill out the registration form with your information, including your student LRN and graduation year. Once submitted, your account will be verified within 24-48 hours.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    How can I search for jobs? <i class="ri-arrow-down-s-line faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    After logging in, click on "Find Jobs Now" on the homepage or navigate to the Job Portal section.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    Are the job listings exclusive to ASKI alumni? <i class="ri-arrow-down-s-line faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Yes, many of the job listings on our platform are exclusively available to ASKI alumni. Employers specifically seek out ASKI alumni for their skills and training. Some listings may be open to the general public, but ASKI alumni are given priority consideration.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question">
                    How can I update my profile and resume? <i class="ri-arrow-down-s-line faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    After logging in, go to your profile page by clicking on your name in the top right corner. From there, you can edit your personal information, update your work experience, education, skills, and upload a new resume. Keep your profile updated to improve your chances of being matched with relevant job opportunities.
                </div>
            </div>
        </div>
    </div>

    <script>
        // Create animated network connections
        document.addEventListener('DOMContentLoaded', function() {
            createNetworkConnections();
            addParallaxEffect();
            setupMobileNavigation();
        });
        
        function createNetworkConnections() {
            const networkContainer = document.getElementById('network-container');
            const canvas = document.createElement('canvas');
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            networkContainer.appendChild(canvas);
            
            const ctx = canvas.getContext('2d');
            
            // Create nodes representing job connections
            const nodes = [];
            const nodeCount = 20;
            
            for (let i = 0; i < nodeCount; i++) {
                nodes.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    radius: Math.random() * 3 + 2
                });
            }
            
            function drawNodes() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Update node positions
                nodes.forEach(node => {
                    node.x += node.vx;
                    node.y += node.vy;
                    
                    // Bounce off edges
                    if (node.x < 0 || node.x > canvas.width) node.vx *= -1;
                    if (node.y < 0 || node.y > canvas.height) node.vy *= -1;
                    
                    // Draw node
                    ctx.beginPath();
                    ctx.arc(node.x, node.y, node.radius, 0, Math.PI * 2);
                    ctx.fillStyle = '#1e3a8a';
                    ctx.fill();
                });
                
                // Draw connections between nodes (representing job network)
                for (let i = 0; i < nodes.length; i++) {
                    for (let j = i + 1; j < nodes.length; j++) {
                        const dx = nodes[i].x - nodes[j].x;
                        const dy = nodes[i].y - nodes[j].y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        
                        if (distance < 150) {
                            ctx.beginPath();
                            ctx.moveTo(nodes[i].x, nodes[i].y);
                            ctx.lineTo(nodes[j].x, nodes[j].y);
                            ctx.strokeStyle = `rgba(30, 58, 138, ${0.1 - distance / 1500})`;
                            ctx.lineWidth = 1;
                            ctx.stroke();
                        }
                    }
                }
                
                requestAnimationFrame(drawNodes);
            }
            
            drawNodes();
            
            // Resize canvas on window resize
            window.addEventListener('resize', function() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            });
        }
        
        function addParallaxEffect() {
            document.addEventListener('mousemove', function(e) {
                const moveX = (e.clientX - window.innerWidth / 2) / 50;
                const moveY = (e.clientY - window.innerHeight / 2) / 50;
                
                const jobBubbles = document.querySelectorAll('.job-bubble');
                const documents = document.querySelectorAll('.document');
                const careerArrows = document.querySelectorAll('.career-arrow');
                
                jobBubbles.forEach((bubble, index) => {
                    const factor = index % 2 === 0 ? 1 : -1;
                    bubble.style.transform = `translate(${moveX * factor}px, ${moveY * factor}px)`;
                });
                
                documents.forEach((doc, index) => {
                    const factor = (index % 3 - 1) * 0.8;
                    doc.style.transform = `translate(${moveX * factor}px, ${moveY * factor}px) rotate(${moveX/2}deg)`;
                });
                
                careerArrows.forEach((arrow, index) => {
                    const factor = (index % 3 - 1) * 1.2;
                    arrow.style.transform = `translate(${moveX * factor}px, ${moveY * factor}px)`;
                });
            });
        }
        
        function setupMobileNavigation() {
            // Logo/brand click returns to home
            document.getElementById('homeLink').addEventListener('click', function() {
                closeAllPanels();
            });
            
            // Return buttons in each panel
            document.querySelectorAll('.return-button').forEach(button => {
                button.addEventListener('click', function() {
                    closeAllPanels();
                });
            });
            
            // Mobile menu toggle
            const menuToggle = document.getElementById('menuToggle');
            const mobileMenu = document.getElementById('mobileMenu');
            
            menuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('active');
                menuToggle.innerHTML = mobileMenu.classList.contains('active') 
                    ? '<i class="ri-close-line"></i>' 
                    : '<i class="ri-menu-line"></i>';
            });

            // Panel functionality
            const panelTriggers = document.querySelectorAll('.panel-trigger');
            const panels = {
                history: document.getElementById('historyPanel'),
                contacts: document.getElementById('contactsPanel'),
                about: document.getElementById('aboutPanel'),
                resources: document.getElementById('resourcesPanel'),
                faqs: document.getElementById('faqsPanel')
            };
            const closeButtons = document.querySelectorAll('.close-panel');
            const findJobsBtn = document.querySelector('.find-jobs-btn');
            
            // Show panel when link is clicked
            panelTriggers.forEach(trigger => {
                trigger.addEventListener('click', function() {
                    const panelId = this.getAttribute('data-panel');
                    
                    // Add click animation
                    this.style.transform = 'translateY(2px)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 200);
                    
                    // Close all panels first
                    closeAllPanels();
                    
                    // Open the selected panel with a slight delay for smoother animation
                    setTimeout(() => {
                        panels[panelId].classList.add('active');
                    }, 50);
                    
                    // Close mobile menu if open
                    mobileMenu.classList.remove('active');
                    menuToggle.innerHTML = '<i class="ri-menu-line"></i>';
                });
            });
            
            // Find Jobs button opens registration page
            findJobsBtn.addEventListener('click', () => {
                window.location.href = 'registration.php';
            });
            
            // Close panel when close button is clicked
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.content-panel').classList.remove('active');
                });
            });
            
            // FAQ functionality
            document.querySelectorAll('.faq-question').forEach(question => {
                question.addEventListener('click', () => {
                    const item = question.closest('.faq-item');
                    document.querySelectorAll('.faq-item').forEach(i => {
                        if (i !== item) i.classList.remove('active');
                    });
                    item.classList.toggle('active');
                });
            });
        }

        // Helper function to close all panels
        function closeAllPanels() {
            document.querySelectorAll('.content-panel').forEach(panel => {
                panel.classList.remove('active');
            });
        }
    </script>
</body>
</html>