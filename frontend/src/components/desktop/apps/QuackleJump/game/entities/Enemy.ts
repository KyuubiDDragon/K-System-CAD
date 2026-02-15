export type EnemyType = 'bat' | 'windstorm' | 'lightning';

export class Enemy {
  x: number;
  y: number;
  width: number = 40;
  height: number = 40;
  type: EnemyType;
  
  // Movement
  private velocityX: number = 0;
  private velocityY: number = 0;
  private movePattern: 'horizontal' | 'vertical' | 'circular' = 'horizontal';
  private moveSpeed: number = 100;
  private initialX: number;
  private initialY: number;
  
  // State
  private isDefeated: boolean = false;
  private defeatAnimation: number = 0;
  private animationTime: number = 0;
  
  constructor(x: number, y: number, type: EnemyType) {
    this.x = x;
    this.y = y;
    this.type = type;
    this.initialX = x;
    this.initialY = y;
    
    // Set movement pattern based on type
    switch (type) {
      case 'bat':
        this.movePattern = 'horizontal';
        this.velocityX = (Math.random() > 0.5 ? 1 : -1) * this.moveSpeed;
        break;
      case 'windstorm':
        this.movePattern = 'circular';
        break;
      case 'lightning':
        this.movePattern = 'vertical';
        this.moveSpeed = 200;
        break;
    }
  }
  
  update(deltaTime: number) {
    if (this.isDefeated) {
      this.defeatAnimation += deltaTime;
      this.y += 300 * deltaTime; // Fall down
      return;
    }
    
    this.animationTime += deltaTime;
    
    // Update position based on movement pattern
    switch (this.movePattern) {
      case 'horizontal':
        this.x += this.velocityX * deltaTime;
        
        // Bounce off edges
        if (this.x < 20 || this.x > 380) {
          this.velocityX *= -1;
        }
        
        // Slight vertical movement for bats
        if (this.type === 'bat') {
          this.y += Math.sin(this.animationTime * 3) * 20 * deltaTime;
        }
        break;
        
      case 'circular':
        // Circular movement for windstorm
        const radius = 50;
        this.x = this.initialX + Math.cos(this.animationTime * 2) * radius;
        this.y = this.initialY + Math.sin(this.animationTime * 2) * radius;
        break;
        
      case 'vertical':
        // Lightning strikes downward
        this.y += this.moveSpeed * deltaTime;
        break;
    }
  }
  
  render(ctx: CanvasRenderingContext2D) {
    ctx.save();
    
    if (this.isDefeated) {
      ctx.globalAlpha = 1 - this.defeatAnimation;
      ctx.translate(0, this.defeatAnimation * 50);
    }
    
    switch (this.type) {
      case 'bat':
        this.drawBat(ctx);
        break;
      case 'windstorm':
        this.drawWindstorm(ctx);
        break;
      case 'lightning':
        this.drawLightning(ctx);
        break;
    }
    
    ctx.restore();
  }
  
  private drawBat(ctx: CanvasRenderingContext2D) {
    // Body
    ctx.fillStyle = '#4B0082';
    ctx.beginPath();
    ctx.ellipse(this.x, this.y, 10, 8, 0, 0, Math.PI * 2);
    ctx.fill();
    
    // Wings
    const wingFlap = Math.sin(this.animationTime * 10) * 0.3;
    
    ctx.fillStyle = '#2F0147';
    
    // Left wing
    ctx.beginPath();
    ctx.moveTo(this.x - 10, this.y);
    ctx.quadraticCurveTo(
      this.x - 20 - wingFlap * 10, this.y - 5,
      this.x - 25, this.y + 5
    );
    ctx.quadraticCurveTo(
      this.x - 20, this.y + 8,
      this.x - 10, this.y + 5
    );
    ctx.closePath();
    ctx.fill();
    
    // Right wing
    ctx.beginPath();
    ctx.moveTo(this.x + 10, this.y);
    ctx.quadraticCurveTo(
      this.x + 20 + wingFlap * 10, this.y - 5,
      this.x + 25, this.y + 5
    );
    ctx.quadraticCurveTo(
      this.x + 20, this.y + 8,
      this.x + 10, this.y + 5
    );
    ctx.closePath();
    ctx.fill();
    
    // Eyes
    ctx.fillStyle = '#FF0000';
    ctx.beginPath();
    ctx.arc(this.x - 4, this.y - 2, 2, 0, Math.PI * 2);
    ctx.arc(this.x + 4, this.y - 2, 2, 0, Math.PI * 2);
    ctx.fill();
  }
  
  private drawWindstorm(ctx: CanvasRenderingContext2D) {
    // Swirling wind effect
    ctx.strokeStyle = 'rgba(135, 206, 235, 0.6)';
    ctx.lineWidth = 3;
    
    for (let i = 0; i < 3; i++) {
      ctx.beginPath();
      const offset = i * (Math.PI * 2 / 3);
      const size = 15 + i * 5;
      
      for (let angle = 0; angle < Math.PI * 2; angle += 0.1) {
        const r = size * (1 + 0.1 * Math.sin(angle * 3 + this.animationTime * 5 + offset));
        const x = this.x + Math.cos(angle + this.animationTime * 2) * r;
        const y = this.y + Math.sin(angle + this.animationTime * 2) * r;
        
        if (angle === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }
      }
      
      ctx.closePath();
      ctx.stroke();
    }
    
    // Center vortex
    ctx.fillStyle = 'rgba(70, 130, 180, 0.4)';
    ctx.beginPath();
    ctx.arc(this.x, this.y, 8, 0, Math.PI * 2);
    ctx.fill();
  }
  
  private drawLightning(ctx: CanvasRenderingContext2D) {
    // Lightning bolt
    ctx.fillStyle = '#FFFF00';
    ctx.strokeStyle = '#FFD700';
    ctx.lineWidth = 2;
    
    ctx.beginPath();
    ctx.moveTo(this.x, this.y - 20);
    ctx.lineTo(this.x - 8, this.y - 5);
    ctx.lineTo(this.x + 3, this.y - 5);
    ctx.lineTo(this.x - 5, this.y + 10);
    ctx.lineTo(this.x + 5, this.y - 8);
    ctx.lineTo(this.x - 3, this.y - 8);
    ctx.lineTo(this.x + 8, this.y - 20);
    ctx.closePath();
    ctx.fill();
    ctx.stroke();
    
    // Electric glow
    ctx.shadowColor = '#FFFF00';
    ctx.shadowBlur = 10 + Math.random() * 5;
    ctx.fill();
    ctx.shadowBlur = 0;
  }
  
  defeat() {
    this.isDefeated = true;
  }
  
  isActive(): boolean {
    return !this.isDefeated || this.defeatAnimation < 1;
  }
}