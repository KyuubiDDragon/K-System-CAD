import { Quackle } from './Quackle';

export type PowerUpType = 'jetpack' | 'superJump' | 'shield' | 'balloon';

export class PowerUp {
  x: number;
  y: number;
  width: number = 30;
  height: number = 30;
  type: PowerUpType;
  
  // Animation
  private animationTime: number = 0;
  private collected: boolean = false;
  
  constructor(x: number, y: number, type: PowerUpType) {
    this.x = x;
    this.y = y;
    this.type = type;
  }
  
  update(deltaTime: number) {
    this.animationTime += deltaTime;
  }
  
  render(ctx: CanvasRenderingContext2D) {
    if (this.collected) return;
    
    ctx.save();
    
    // Floating animation
    const floatOffset = Math.sin(this.animationTime * 3) * 5;
    ctx.translate(0, floatOffset);
    
    // Glow effect
    const glowSize = 20 + Math.sin(this.animationTime * 5) * 5;
    const gradient = ctx.createRadialGradient(this.x, this.y, 0, this.x, this.y, glowSize);
    gradient.addColorStop(0, this.getGlowColor());
    gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
    ctx.fillStyle = gradient;
    ctx.fillRect(this.x - glowSize, this.y - glowSize, glowSize * 2, glowSize * 2);
    
    // Draw power-up icon
    switch (this.type) {
      case 'jetpack':
        this.drawJetpack(ctx);
        break;
      case 'superJump':
        this.drawSuperJump(ctx);
        break;
      case 'shield':
        this.drawShield(ctx);
        break;
      case 'balloon':
        this.drawBalloon(ctx);
        break;
    }
    
    ctx.restore();
  }
  
  private drawJetpack(ctx: CanvasRenderingContext2D) {
    // Jetpack body
    ctx.fillStyle = '#8B008B';
    ctx.fillRect(this.x - 10, this.y - 15, 20, 25);
    
    // Jetpack nozzles
    ctx.fillStyle = '#696969';
    ctx.fillRect(this.x - 12, this.y + 10, 6, 8);
    ctx.fillRect(this.x + 6, this.y + 10, 6, 8);
    
    // Straps
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(this.x - 8, this.y - 10);
    ctx.lineTo(this.x - 8, this.y + 5);
    ctx.moveTo(this.x + 8, this.y - 10);
    ctx.lineTo(this.x + 8, this.y + 5);
    ctx.stroke();
  }
  
  private drawSuperJump(ctx: CanvasRenderingContext2D) {
    // Star shape
    ctx.fillStyle = '#FFD700';
    this.drawStar(ctx, this.x, this.y, 15, 5);
    
    // Inner star for effect
    ctx.fillStyle = '#FFA500';
    this.drawStar(ctx, this.x, this.y, 8, 5);
  }
  
  private drawShield(ctx: CanvasRenderingContext2D) {
    // Shield shape
    ctx.fillStyle = '#4169E1';
    ctx.beginPath();
    ctx.moveTo(this.x, this.y - 15);
    ctx.lineTo(this.x - 12, this.y - 8);
    ctx.lineTo(this.x - 12, this.y + 5);
    ctx.quadraticCurveTo(this.x, this.y + 15, this.x + 12, this.y + 5);
    ctx.lineTo(this.x + 12, this.y - 8);
    ctx.closePath();
    ctx.fill();
    
    // Shield emblem
    ctx.fillStyle = '#87CEEB';
    ctx.beginPath();
    ctx.arc(this.x, this.y - 2, 5, 0, Math.PI * 2);
    ctx.fill();
  }
  
  private drawBalloon(ctx: CanvasRenderingContext2D) {
    // Balloon string
    ctx.strokeStyle = '#666';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(this.x, this.y + 10);
    ctx.lineTo(this.x, this.y + 20);
    ctx.stroke();
    
    // Balloon
    ctx.fillStyle = '#FF69B4';
    ctx.beginPath();
    ctx.ellipse(this.x, this.y, 12, 15, 0, 0, Math.PI * 2);
    ctx.fill();
    
    // Highlight
    ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
    ctx.beginPath();
    ctx.ellipse(this.x - 4, this.y - 5, 4, 6, -Math.PI / 4, 0, Math.PI * 2);
    ctx.fill();
  }
  
  private drawStar(ctx: CanvasRenderingContext2D, cx: number, cy: number, outerRadius: number, points: number) {
    const innerRadius = outerRadius * 0.5;
    ctx.beginPath();
    
    for (let i = 0; i < points * 2; i++) {
      const angle = (i * Math.PI) / points - Math.PI / 2;
      const radius = i % 2 === 0 ? outerRadius : innerRadius;
      const x = cx + Math.cos(angle) * radius;
      const y = cy + Math.sin(angle) * radius;
      
      if (i === 0) {
        ctx.moveTo(x, y);
      } else {
        ctx.lineTo(x, y);
      }
    }
    
    ctx.closePath();
    ctx.fill();
  }
  
  private getGlowColor(): string {
    switch (this.type) {
      case 'jetpack': return 'rgba(139, 0, 139, 0.3)';
      case 'superJump': return 'rgba(255, 215, 0, 0.3)';
      case 'shield': return 'rgba(65, 105, 225, 0.3)';
      case 'balloon': return 'rgba(255, 105, 180, 0.3)';
    }
  }
  
  collect(player: Quackle) {
    if (this.collected) return;
    
    this.collected = true;
    
    switch (this.type) {
      case 'jetpack':
        player.activateJetpack();
        break;
      case 'superJump':
        player.activateSuperJump();
        break;
      case 'shield':
        player.activateShield();
        break;
      case 'balloon':
        player.activateBalloon();
        break;
    }
  }
}