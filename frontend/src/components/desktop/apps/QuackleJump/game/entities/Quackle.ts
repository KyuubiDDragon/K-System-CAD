import { Platform } from './Platform';

export class Quackle {
  // Position and physics
  x: number;
  y: number;
  velocityX: number = 0;
  velocityY: number = 0;
  targetX: number;
  
  // Size
  width: number = 40;
  height: number = 50;
  
  // Physics constants
  private gravity: number = 1200;
  private jumpForce: number = -600;
  private moveSpeed: number = 300;
  private maxFallSpeed: number = 800;
  
  // Power-up states
  hasJetpack: boolean = false;
  jetpackTime: number = 0;
  hasSuperJump: boolean = false;
  superJumpTime: number = 0;
  hasShield: boolean = false;
  shieldTime: number = 0;
  hasBalloon: boolean = false;
  balloonTime: number = 0;
  
  // Animation
  private animationTime: number = 0;
  private facing: 'left' | 'right' = 'right';
  
  // Keyboard movement
  private isMovingLeft: boolean = false;
  private isMovingRight: boolean = false;
  
  constructor(x: number, y: number) {
    this.x = x;
    this.y = y;
    this.targetX = x;
  }
  
  update(deltaTime: number) {
    // Update animation time
    this.animationTime += deltaTime;
    
    // Keyboard movement
    if (this.isMovingLeft) {
      this.velocityX = -this.moveSpeed;
      this.facing = 'left';
    } else if (this.isMovingRight) {
      this.velocityX = this.moveSpeed;
      this.facing = 'right';
    } else {
      // Decelerate when no keys pressed
      this.velocityX *= 0.8;
    }
    
    // Apply horizontal movement
    this.x += this.velocityX * deltaTime;
    
    // Vertical physics
    if (this.hasJetpack && this.jetpackTime > 0) {
      // Jetpack physics
      this.velocityY = -400;
      this.jetpackTime -= deltaTime;
      if (this.jetpackTime <= 0) {
        this.hasJetpack = false;
      }
    } else {
      // Normal gravity
      let effectiveGravity = this.gravity;
      if (this.hasBalloon && this.balloonTime > 0) {
        effectiveGravity *= 0.3; // Slower fall with balloon
        this.balloonTime -= deltaTime;
        if (this.balloonTime <= 0) {
          this.hasBalloon = false;
        }
      }
      
      this.velocityY += effectiveGravity * deltaTime;
      
      // Cap fall speed
      if (this.velocityY > this.maxFallSpeed) {
        this.velocityY = this.maxFallSpeed;
      }
    }
    
    // Update position
    this.y += this.velocityY * deltaTime;
    
    // Update power-up timers
    if (this.hasSuperJump && this.superJumpTime > 0) {
      this.superJumpTime -= deltaTime;
      if (this.superJumpTime <= 0) {
        this.hasSuperJump = false;
      }
    }
    
    if (this.hasShield && this.shieldTime > 0) {
      this.shieldTime -= deltaTime;
      if (this.shieldTime <= 0) {
        this.hasShield = false;
      }
    }
    
    // Keep player on screen horizontally
    if (this.x < this.width / 2) {
      this.x = this.width / 2;
    } else if (this.x > 400 - this.width / 2) {
      this.x = 400 - this.width / 2;
    }
  }
  
  render(ctx: CanvasRenderingContext2D) {
    ctx.save();
    
    // Draw shield if active
    if (this.hasShield) {
      ctx.strokeStyle = '#00BFFF';
      ctx.lineWidth = 3;
      ctx.globalAlpha = 0.5 + Math.sin(this.animationTime * 5) * 0.2;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.width * 0.8, 0, Math.PI * 2);
      ctx.stroke();
      ctx.globalAlpha = 1;
    }
    
    // Draw jetpack flames if active
    if (this.hasJetpack && this.jetpackTime > 0) {
      this.drawJetpackFlames(ctx);
    }
    
    // Draw Quackle (simple duck shape)
    this.drawDuck(ctx);
    
    // Draw balloon if active
    if (this.hasBalloon && this.balloonTime > 0) {
      this.drawBalloon(ctx);
    }
    
    ctx.restore();
  }
  
  private drawDuck(ctx: CanvasRenderingContext2D) {
    // Body
    ctx.fillStyle = '#FFD700';
    ctx.beginPath();
    ctx.ellipse(this.x, this.y, this.width / 2, this.height / 2, 0, 0, Math.PI * 2);
    ctx.fill();
    
    // Head
    ctx.beginPath();
    ctx.arc(this.x, this.y - this.height / 3, this.width / 3, 0, Math.PI * 2);
    ctx.fill();
    
    // Eye
    ctx.fillStyle = '#000';
    const eyeX = this.x + (this.facing === 'right' ? 5 : -5);
    ctx.beginPath();
    ctx.arc(eyeX, this.y - this.height / 3, 3, 0, Math.PI * 2);
    ctx.fill();
    
    // Beak
    ctx.fillStyle = '#FF6347';
    ctx.beginPath();
    const beakX = this.x + (this.facing === 'right' ? this.width / 3 : -this.width / 3);
    ctx.moveTo(beakX, this.y - this.height / 3);
    ctx.lineTo(beakX + (this.facing === 'right' ? 10 : -10), this.y - this.height / 3 + 5);
    ctx.lineTo(beakX, this.y - this.height / 3 + 10);
    ctx.closePath();
    ctx.fill();
    
    // Wing (flapping animation)
    ctx.fillStyle = '#FFA500';
    const wingFlap = Math.sin(this.animationTime * 10) * 5;
    ctx.beginPath();
    ctx.ellipse(
      this.x + (this.facing === 'right' ? -5 : 5),
      this.y,
      this.width / 4,
      this.height / 3 + wingFlap,
      0, 0, Math.PI * 2
    );
    ctx.fill();
  }
  
  private drawJetpackFlames(ctx: CanvasRenderingContext2D) {
    const flameHeight = 20 + Math.random() * 10;
    
    // Gradient for flames
    const gradient = ctx.createLinearGradient(
      this.x - 10, this.y + this.height / 2,
      this.x - 10, this.y + this.height / 2 + flameHeight
    );
    gradient.addColorStop(0, '#FF6347');
    gradient.addColorStop(0.5, '#FFA500');
    gradient.addColorStop(1, 'rgba(255, 165, 0, 0)');
    
    ctx.fillStyle = gradient;
    
    // Left flame
    ctx.beginPath();
    ctx.moveTo(this.x - 15, this.y + this.height / 2);
    ctx.lineTo(this.x - 10, this.y + this.height / 2 + flameHeight);
    ctx.lineTo(this.x - 5, this.y + this.height / 2);
    ctx.closePath();
    ctx.fill();
    
    // Right flame
    ctx.beginPath();
    ctx.moveTo(this.x + 5, this.y + this.height / 2);
    ctx.lineTo(this.x + 10, this.y + this.height / 2 + flameHeight);
    ctx.lineTo(this.x + 15, this.y + this.height / 2);
    ctx.closePath();
    ctx.fill();
  }
  
  private drawBalloon(ctx: CanvasRenderingContext2D) {
    // Balloon string
    ctx.strokeStyle = '#666';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(this.x, this.y - this.height / 2);
    ctx.lineTo(this.x, this.y - this.height / 2 - 30);
    ctx.stroke();
    
    // Balloon
    ctx.fillStyle = '#FF69B4';
    ctx.beginPath();
    ctx.ellipse(this.x, this.y - this.height / 2 - 40, 15, 20, 0, 0, Math.PI * 2);
    ctx.fill();
    
    // Highlight
    ctx.fillStyle = 'rgba(255, 255, 255, 0.5)';
    ctx.beginPath();
    ctx.ellipse(this.x - 5, this.y - this.height / 2 - 45, 5, 8, -Math.PI / 4, 0, Math.PI * 2);
    ctx.fill();
  }
  
  land(platform: Platform) {
    this.y = platform.y - platform.height / 2 - this.height / 2;
    
    let jumpMultiplier = 1;
    if (this.hasSuperJump && this.superJumpTime > 0) {
      jumpMultiplier = 3;
    }
    if (platform.type === 'spring') {
      jumpMultiplier *= 1.5;
    }
    
    this.velocityY = this.jumpForce * jumpMultiplier;
  }
  
  bounce() {
    this.velocityY = this.jumpForce * 0.8;
  }
  
  isColliding(entity: { x: number; y: number; width: number; height: number }): boolean {
    return (
      Math.abs(this.x - entity.x) < (this.width + entity.width) / 2 &&
      Math.abs(this.y - entity.y) < (this.height + entity.height) / 2
    );
  }
  
  // Power-up methods
  activateJetpack() {
    this.hasJetpack = true;
    this.jetpackTime = 5; // 5 seconds
  }
  
  activateSuperJump() {
    this.hasSuperJump = true;
    this.superJumpTime = 10; // 10 seconds
  }
  
  activateShield() {
    this.hasShield = true;
    this.shieldTime = 10; // 10 seconds
  }
  
  activateBalloon() {
    this.hasBalloon = true;
    this.balloonTime = 3; // 3 seconds
  }
  
  usePowerUp() {
    // Activate stored power-ups if any
    // This could be expanded to allow storing power-ups for later use
  }
  
  // Keyboard movement methods
  moveLeft() {
    this.isMovingLeft = true;
    this.isMovingRight = false;
  }
  
  moveRight() {
    this.isMovingRight = true;
    this.isMovingLeft = false;
  }
  
  stopMoving() {
    this.isMovingLeft = false;
    this.isMovingRight = false;
  }
}