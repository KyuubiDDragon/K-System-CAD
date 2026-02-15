import { Quackle } from './Quackle';

export type PlatformType = 'normal' | 'fragile' | 'moving' | 'spring';

export class Platform {
  x: number;
  y: number;
  width: number = 80;
  height: number = 15;
  type: PlatformType;
  
  // State
  private isDestroyed: boolean = false;
  private destroyTimer: number = 0;
  
  // Moving platform properties
  private moveDirection: number = 1;
  private moveSpeed: number = 50;
  private moveRange: number = 100;
  private initialX: number;
  
  // Spring animation
  private springAnimation: number = 0;
  
  constructor(x: number, y: number, type: PlatformType = 'normal') {
    this.x = x;
    this.y = y;
    this.type = type;
    this.initialX = x;
    
    // Randomize moving platform direction
    if (type === 'moving') {
      this.moveDirection = Math.random() > 0.5 ? 1 : -1;
    }
  }
  
  update(deltaTime: number) {
    // Update moving platforms
    if (this.type === 'moving' && !this.isDestroyed) {
      this.x += this.moveDirection * this.moveSpeed * deltaTime;
      
      // Reverse direction at boundaries
      if (Math.abs(this.x - this.initialX) > this.moveRange) {
        this.moveDirection *= -1;
      }
    }
    
    // Update fragile platform destruction
    if (this.type === 'fragile' && this.isDestroyed) {
      this.destroyTimer += deltaTime;
    }
    
    // Update spring animation
    if (this.springAnimation > 0) {
      this.springAnimation -= deltaTime * 10;
      if (this.springAnimation < 0) {
        this.springAnimation = 0;
      }
    }
  }
  
  render(ctx: CanvasRenderingContext2D) {
    if (this.isDestroyed && this.type === 'fragile') {
      // Draw breaking animation
      this.drawFragilePlatformBreaking(ctx);
      return;
    }
    
    ctx.save();
    
    // Apply spring animation
    if (this.springAnimation > 0) {
      ctx.translate(0, -this.springAnimation * 10);
    }
    
    // Draw platform based on type
    switch (this.type) {
      case 'normal':
        this.drawNormalPlatform(ctx);
        break;
      case 'fragile':
        this.drawFragilePlatform(ctx);
        break;
      case 'moving':
        this.drawMovingPlatform(ctx);
        break;
      case 'spring':
        this.drawSpringPlatform(ctx);
        break;
    }
    
    ctx.restore();
  }
  
  private drawNormalPlatform(ctx: CanvasRenderingContext2D) {
    // Wood texture
    ctx.fillStyle = '#8B4513';
    ctx.fillRect(this.x - this.width / 2, this.y - this.height / 2, this.width, this.height);
    
    // Wood grain
    ctx.strokeStyle = '#654321';
    ctx.lineWidth = 1;
    for (let i = 0; i < 3; i++) {
      ctx.beginPath();
      ctx.moveTo(this.x - this.width / 2, this.y - this.height / 2 + i * 5 + 2);
      ctx.lineTo(this.x + this.width / 2, this.y - this.height / 2 + i * 5 + 2);
      ctx.stroke();
    }
  }
  
  private drawFragilePlatform(ctx: CanvasRenderingContext2D) {
    // Cracked ice appearance
    ctx.fillStyle = '#B0E0E6';
    ctx.fillRect(this.x - this.width / 2, this.y - this.height / 2, this.width, this.height);
    
    // Cracks
    ctx.strokeStyle = '#4682B4';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(this.x - 10, this.y);
    ctx.lineTo(this.x + 10, this.y - 5);
    ctx.moveTo(this.x + 5, this.y);
    ctx.lineTo(this.x + 15, this.y + 5);
    ctx.stroke();
  }
  
  private drawMovingPlatform(ctx: CanvasRenderingContext2D) {
    // Metal appearance
    ctx.fillStyle = '#C0C0C0';
    ctx.fillRect(this.x - this.width / 2, this.y - this.height / 2, this.width, this.height);
    
    // Metal shine
    ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
    ctx.fillRect(this.x - this.width / 2, this.y - this.height / 2, this.width, this.height / 3);
    
    // Bolts
    ctx.fillStyle = '#666';
    ctx.beginPath();
    ctx.arc(this.x - this.width / 2 + 10, this.y, 3, 0, Math.PI * 2);
    ctx.arc(this.x + this.width / 2 - 10, this.y, 3, 0, Math.PI * 2);
    ctx.fill();
  }
  
  private drawSpringPlatform(ctx: CanvasRenderingContext2D) {
    // Base platform
    ctx.fillStyle = '#32CD32';
    ctx.fillRect(this.x - this.width / 2, this.y - this.height / 2, this.width, this.height);
    
    // Spring coil
    ctx.strokeStyle = '#228B22';
    ctx.lineWidth = 3;
    ctx.beginPath();
    const coils = 5;
    const coilHeight = 3;
    for (let i = 0; i < coils; i++) {
      const y1 = this.y + this.height / 2 + i * coilHeight;
      const y2 = y1 + coilHeight;
      ctx.moveTo(this.x - 10, y1);
      ctx.bezierCurveTo(
        this.x - 5, y1 + coilHeight / 2,
        this.x + 5, y1 + coilHeight / 2,
        this.x + 10, y2
      );
    }
    ctx.stroke();
  }
  
  private drawFragilePlatformBreaking(ctx: CanvasRenderingContext2D) {
    // Draw platform pieces falling apart
    const pieces = 4;
    const breakProgress = Math.min(this.destroyTimer / 0.5, 1);
    
    ctx.globalAlpha = 1 - breakProgress;
    
    for (let i = 0; i < pieces; i++) {
      const pieceX = this.x - this.width / 2 + i * (this.width / pieces) + (Math.random() - 0.5) * 20 * breakProgress;
      const pieceY = this.y + breakProgress * 50 + Math.random() * 20;
      const pieceWidth = this.width / pieces;
      
      ctx.fillStyle = '#B0E0E6';
      ctx.fillRect(pieceX, pieceY - this.height / 2, pieceWidth - 2, this.height);
    }
    
    ctx.globalAlpha = 1;
  }
  
  onLand(player: Quackle) {
    switch (this.type) {
      case 'fragile':
        // Destroy platform after landing
        this.isDestroyed = true;
        break;
      case 'spring':
        // Trigger spring animation
        this.springAnimation = 1;
        break;
    }
  }
  
  isActive(): boolean {
    return !this.isDestroyed || (this.type === 'fragile' && this.destroyTimer < 0.5);
  }
}