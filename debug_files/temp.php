<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psychedelic Spinning Spiral</title>
    <style>
        body {
            margin: 0;
            overflow: hidden;
            background-color: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        canvas {
            display: block;
        }
        .controls {
            position: absolute;
            bottom: 20px;
            background: rgba(0,0,0,0.7);
            padding: 10px 20px;
            border-radius: 10px;
            color: white;
            font-family: Arial, sans-serif;
        }
        .controls button {
            background: #4CAF50;
            border: none;
            color: white;
            padding: 5px 10px;
            margin: 0 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .controls button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <canvas id="spiralCanvas"></canvas>
    <div class="controls">
        <button id="speedDown">Slower</button>
        <button id="speedUp">Faster</button>
        <button id="directionToggle">Reverse</button>
        <button id="colorToggle">Change Colors</button>
    </div>

    <script>
        const canvas = document.getElementById('spiralCanvas');
        const ctx = canvas.getContext('2d');
        
        // Set canvas to window size
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        
        // Spiral parameters
        let time = 0;
        let rotationSpeed = 0.03;
        let direction = 1;
        let colorScheme = 0;
        const colorSchemes = [
            // Rainbow
            (t) => `hsl(${(t * 20) % 360}, 100%, 50%)`,
            // Blue-Purple
            (t) => `hsl(${(t * 10 % 60) + 240}, 100%, 50%)`,
            // Red-Yellow
            (t) => `hsl(${(t * 10 % 60) + 0}, 100%, 50%)`,
            // Green-Cyan
            (t) => `hsl(${(t * 10 % 60) + 120}, 100%, 50%)`
        ];
        
        // Handle window resize
        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
        
        function drawSpiral() {
            // Clear canvas
            ctx.fillStyle = 'rgba(0, 0, 0, 0.1)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            
            // Maximum radius based on screen size
            const maxRadius = Math.min(canvas.width, canvas.height) * 0.45;
            
            // Draw spiral
            for (let i = 0; i < 200; i++) {
                const angle = 0.1 * i + time * direction;
                const radius = (i / 200) * maxRadius;
                
                const x = centerX + Math.cos(angle) * radius;
                const y = centerY + Math.sin(angle) * radius;
                
                // Size grows with radius
                const size = (radius / maxRadius) * 15 + 2;
                
                // Color based on angle and time
                ctx.fillStyle = colorSchemes[colorScheme]((angle + time) * 0.2);
                
                ctx.beginPath();
                ctx.arc(x, y, size, 0, Math.PI * 2);
                ctx.fill();
            }
            
            // Update time
            time += rotationSpeed;
            
            // Animation loop
            requestAnimationFrame(drawSpiral);
        }
        
        // Start animation
        drawSpiral();
        
        // Controls
        document.getElementById('speedUp').addEventListener('click', () => {
            rotationSpeed += 0.01;
        });
        
        document.getElementById('speedDown').addEventListener('click', () => {
            rotationSpeed = Math.max(0.01, rotationSpeed - 0.01);
        });
        
        document.getElementById('directionToggle').addEventListener('click', () => {
            direction *= -1;
        });
        
        document.getElementById('colorToggle').addEventListener('click', () => {
            colorScheme = (colorScheme + 1) % colorSchemes.length;
        });
    </script>
</body>
</html>