// Rain Animation
(function() {
    const canvas = document.getElementById('rainCanvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    let width = canvas.width = window.innerWidth;
    let height = canvas.height = window.innerHeight;
    
    // Rain drops
    const drops = [];
    const DROP_COUNT = 150;
    const MAX_SPEED = 15;
    const MIN_SPEED = 5;
    const MAX_LENGTH = 20;
    const MIN_LENGTH = 10;
    
    class Drop {
        constructor() {
            this.reset();
        }
        
        reset() {
            this.x = Math.random() * width;
            this.y = Math.random() * -height;
            this.length = Math.random() * (MAX_LENGTH - MIN_LENGTH) + MIN_LENGTH;
            this.speed = Math.random() * (MAX_SPEED - MIN_SPEED) + MIN_SPEED;
            this.opacity = Math.random() * 0.5 + 0.2;
        }
        
        update() {
            this.y += this.speed;
            if (this.y > height) {
                this.reset();
                this.y = -this.length;
            }
        }
        
        draw() {
            ctx.beginPath();
            ctx.moveTo(this.x, this.y);
            ctx.lineTo(this.x, this.y + this.length);
            ctx.strokeStyle = `rgba(174, 194, 224, ${this.opacity})`;
            ctx.lineWidth = 1.5;
            ctx.stroke();
        }
    }
    
    // Initialize drops
    for (let i = 0; i < DROP_COUNT; i++) {
        const drop = new Drop();
        drop.y = Math.random() * height;
        drops.push(drop);
    }
    
    function animate() {
        ctx.clearRect(0, 0, width, height);
        
        drops.forEach(drop => {
            drop.update();
            drop.draw();
        });
        
        requestAnimationFrame(animate);
    }
    
    // Resize handler
    window.addEventListener('resize', function() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    });
    
    animate();
})();