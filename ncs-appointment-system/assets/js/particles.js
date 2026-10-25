// ========================================
// PARTICLE BACKGROUND SYSTEM
// ========================================

(function() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;
    
    let ctx = canvas.getContext('2d');
    let particles = [];
    let particleCount = 80;
    let mouseX = null;
    let mouseY = null;
    let animationId = null;
    
    // Resize canvas
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    // Particle class
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 3 + 1;
            this.speedX = (Math.random() - 0.5) * 0.8;
            this.speedY = (Math.random() - 0.5) * 0.8;
            this.color = `hsl(${Math.random() * 60 + 260}, 70%, 65%)`;
            this.opacity = Math.random() * 0.4 + 0.2;
            this.pulse = Math.random() * Math.PI * 2;
            this.pulseSpeed = Math.random() * 0.03 + 0.01;
        }
        
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            this.pulse += this.pulseSpeed;
            
            // Wrap around edges
            if (this.x < -50) this.x = canvas.width + 50;
            if (this.x > canvas.width + 50) this.x = -50;
            if (this.y < -50) this.y = canvas.height + 50;
            if (this.y > canvas.height + 50) this.y = -50;
            
            // Mouse interaction
            if (mouseX !== null && mouseY !== null) {
                const dx = mouseX - this.x;
                const dy = mouseY - this.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                const maxDistance = 150;
                
                if (distance < maxDistance) {
                    const angle = Math.atan2(dy, dx);
                    const force = (maxDistance - distance) / maxDistance;
                    const pushX = Math.cos(angle) * force * 1.5;
                    const pushY = Math.sin(angle) * force * 1.5;
                    this.x -= pushX;
                    this.y -= pushY;
                }
            }
        }
        
        draw() {
            const pulseSize = this.size + Math.sin(this.pulse) * 0.5;
            ctx.beginPath();
            ctx.arc(this.x, this.y, pulseSize, 0, Math.PI * 2);
            ctx.fillStyle = this.color;
            ctx.globalAlpha = this.opacity;
            ctx.fill();
            
            // Glow effect
            ctx.shadowBlur = 8;
            ctx.shadowColor = this.color;
            ctx.fill();
            ctx.shadowBlur = 0;
        }
    }
    
    // Create particles
    function createParticles() {
        particles = [];
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
    }
    
    // Connect nearby particles with lines
    function drawConnections() {
        const connectionDistance = 120;
        
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < connectionDistance) {
                    const opacity = 0.15 * (1 - distance / connectionDistance);
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(139, 92, 246, ${opacity})`;
                    ctx.lineWidth = 0.8;
                    ctx.stroke();
                }
            }
        }
    }
    
    // Animate particles
    function animate() {
        if (!ctx) return;
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        for (let particle of particles) {
            particle.update();
            particle.draw();
        }
        
        drawConnections();
        
        animationId = requestAnimationFrame(animate);
    }
    
    // Mouse move handler
    function onMouseMove(e) {
        mouseX = e.clientX;
        mouseY = e.clientY;
    }
    
    function onMouseLeave() {
        mouseX = null;
        mouseY = null;
    }
    
    // Handle window resize
    function onResize() {
        resizeCanvas();
        createParticles();
    }
    
    // Initialize
    function init() {
        resizeCanvas();
        createParticles();
        animate();
        
        window.addEventListener('resize', onResize);
        window.addEventListener('mousemove', onMouseMove);
        window.addEventListener('mouseleave', onMouseLeave);
    }
    
    // Start everything
    init();
})();