import { Quackle } from './entities/Quackle';
import { Platform, PlatformType } from './entities/Platform';
import { PowerUp, PowerUpType } from './entities/PowerUp';
import { Enemy } from './entities/Enemy';

interface GameOptions {
  width: number;
  height: number;
  onScoreUpdate: (score: number) => void;
  onGameOver: () => void;
}

export class GameEngine {
  private canvas: HTMLCanvasElement;
  private ctx: CanvasRenderingContext2D;
  private options: GameOptions;
  
  // Game state
  private isRunning: boolean = false;
  private isPaused: boolean = false;
  private lastTime: number = 0;
  private score: number = 0;
  private height: number = 0;
  private startTime: number = 0;
  private currentLevel: number = 1;
  private levelTheme: 'sky' | 'clouds' | 'space' | 'fire' | 'volcano' = 'sky';
  private gameTime: number = 0;
  
  // Game entities
  private player: Quackle;
  private platforms: Platform[] = [];
  private powerUps: PowerUp[] = [];
  private enemies: Enemy[] = [];
  
  // Camera
  private cameraY: number = 0;
  
  // Statistics
  private powerUpsCollected: number = 0;
  private enemiesDefeated: number = 0;
  
  constructor(canvas: HTMLCanvasElement, options: GameOptions) {
    this.canvas = canvas;
    this.ctx = canvas.getContext('2d')!;
    this.options = options;
    
    // Initialize player
    this.player = new Quackle(options.width / 2, options.height - 100);
    
    // Initialize platforms
    this.generateInitialPlatforms();
    
    // Give player initial upward velocity
    this.player.velocityY = -600;
  }
  
  start() {
    this.isRunning = true;
    this.startTime = Date.now();
    this.lastTime = performance.now();
    
    // Add keyboard event listeners
    this.addKeyboardListeners();
    
    // Start the game loop
    requestAnimationFrame(this.gameLoop);
  }
  
  pause() {
    this.isPaused = true;
  }
  
  resume() {
    this.isPaused = false;
    this.gameLoop();
  }
  
  destroy() {
    this.isRunning = false;
    this.removeKeyboardListeners();
  }
  
  private gameLoop = (currentTime: number = 0) => {
    if (!this.isRunning || this.isPaused) return;
    
    // Ensure we have a valid timestamp
    if (!currentTime) currentTime = performance.now();
    
    const deltaTime = Math.min(currentTime - this.lastTime, 100); // Cap deltaTime to avoid huge jumps
    this.lastTime = currentTime;
    
    // Only update if we have a valid deltaTime
    if (deltaTime > 0) {
      this.update(deltaTime / 1000); // Convert to seconds
      this.render();
    }
    
    requestAnimationFrame(this.gameLoop);
  };
  
  private update(deltaTime: number) {
    // Update game time for difficulty scaling
    this.gameTime += deltaTime;
    
    // Update player
    this.player.update(deltaTime);
    
    // Update camera to follow player
    const targetCameraY = this.player.y - this.options.height * 0.6;
    if (targetCameraY < this.cameraY) {
      this.cameraY = targetCameraY;
      
      // Update score based on height
      const newHeight = Math.abs(Math.floor(this.cameraY / 10));
      if (newHeight > this.height) {
        this.height = newHeight;
        this.score = this.height * 10;
        this.options.onScoreUpdate(this.score);
        
        // Check for level progression
        this.checkLevelProgression();
      }
    }
    
    // Check if player fell below screen
    if (this.player.y > this.cameraY + this.options.height + 100) {
      this.gameOver();
      return;
    }
    
    // Update platforms
    this.platforms.forEach(platform => {
      platform.update(deltaTime);
      
      // Check collision with player
      if (this.player.isColliding(platform) && this.player.velocityY > 0) {
        this.player.land(platform);
        platform.onLand(this.player);
      }
    });
    
    // Update power-ups
    this.powerUps = this.powerUps.filter(powerUp => {
      powerUp.update(deltaTime);
      
      // Check collision with player
      if (this.player.isColliding(powerUp)) {
        powerUp.collect(this.player);
        this.powerUpsCollected++;
        return false; // Remove collected power-up
      }
      
      // Remove if too far below
      return powerUp.y < this.cameraY + this.options.height + 100;
    });
    
    // Update enemies
    this.enemies = this.enemies.filter(enemy => {
      enemy.update(deltaTime);
      
      // Check collision with player
      if (this.player.isColliding(enemy) && !this.player.hasShield) {
        if (this.player.velocityY > 0 && this.player.y < enemy.y) {
          // Player jumped on enemy
          enemy.defeat();
          this.player.bounce();
          this.enemiesDefeated++;
          return false;
        } else {
          // Enemy hit player
          this.gameOver();
        }
      }
      
      // Remove if too far below
      return enemy.y < this.cameraY + this.options.height + 100;
    });
    
    // Generate new platforms as needed
    this.generatePlatforms();
    
    // Cleanup old entities
    this.cleanup();
  }
  
  private render() {
    // Clear canvas
    this.ctx.clearRect(0, 0, this.options.width, this.options.height);
    
    // Draw background
    this.drawBackground();
    
    // Save context state
    this.ctx.save();
    
    // Apply camera transform
    this.ctx.translate(0, -this.cameraY);
    
    // Draw platforms
    this.platforms.forEach(platform => platform.render(this.ctx));
    
    // Draw power-ups
    this.powerUps.forEach(powerUp => powerUp.render(this.ctx));
    
    // Draw enemies
    this.enemies.forEach(enemy => enemy.render(this.ctx));
    
    // Draw player
    this.player.render(this.ctx);
    
    // Restore context state
    this.ctx.restore();
    
    // Draw UI overlay
    this.drawUI();
  }
  
  private drawBackground() {
    switch (this.levelTheme) {
      case 'sky':
        this.drawSkyBackground();
        break;
      case 'clouds':
        this.drawCloudsBackground();
        break;
      case 'space':
        this.drawSpaceBackground();
        break;
      case 'fire':
        this.drawFireBackground();
        break;
      case 'volcano':
        this.drawVolcanoBackground();
        break;
    }
    
    // Draw level indicator
    this.drawLevelIndicator();
  }
  
  private drawSkyBackground() {
    // Sky gradient
    const gradient = this.ctx.createLinearGradient(0, 0, 0, this.options.height);
    gradient.addColorStop(0, '#87CEEB');
    gradient.addColorStop(1, '#98D8E8');
    this.ctx.fillStyle = gradient;
    this.ctx.fillRect(0, 0, this.options.width, this.options.height);
    
    // Clouds (parallax effect)
    const cloudY = (this.cameraY * 0.3) % 200;
    this.ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
    for (let i = 0; i < 5; i++) {
      const x = (i * 100 + 50) % this.options.width;
      const y = cloudY + i * 60;
      this.drawCloud(x, y);
    }
  }
  
  private drawCloudsBackground() {
    // Lighter sky with more clouds
    const gradient = this.ctx.createLinearGradient(0, 0, 0, this.options.height);
    gradient.addColorStop(0, '#E6F3FF');
    gradient.addColorStop(1, '#B3D9FF');
    this.ctx.fillStyle = gradient;
    this.ctx.fillRect(0, 0, this.options.width, this.options.height);
    
    // Dense clouds
    const cloudY = (this.cameraY * 0.2) % 150;
    this.ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
    for (let i = 0; i < 8; i++) {
      const x = (i * 60 + 30) % this.options.width;
      const y = cloudY + i * 40;
      this.drawCloud(x, y);
    }
  }
  
  private drawSpaceBackground() {
    // Dark space gradient
    const gradient = this.ctx.createLinearGradient(0, 0, 0, this.options.height);
    gradient.addColorStop(0, '#000428');
    gradient.addColorStop(1, '#004e92');
    this.ctx.fillStyle = gradient;
    this.ctx.fillRect(0, 0, this.options.width, this.options.height);
    
    // Stars
    this.ctx.fillStyle = 'white';
    const starOffset = (this.cameraY * 0.1) % 200;
    for (let i = 0; i < 50; i++) {
      const x = (i * 31 + 17) % this.options.width;
      const y = (starOffset + i * 23) % this.options.height;
      const size = (i % 3) + 1;
      this.ctx.beginPath();
      this.ctx.arc(x, y, size, 0, Math.PI * 2);
      this.ctx.fill();
    }
  }
  
  private drawFireBackground() {
    // Fire/lava gradient
    const gradient = this.ctx.createLinearGradient(0, 0, 0, this.options.height);
    gradient.addColorStop(0, '#FF4500');
    gradient.addColorStop(0.5, '#FF6347');
    gradient.addColorStop(1, '#8B0000');
    this.ctx.fillStyle = gradient;
    this.ctx.fillRect(0, 0, this.options.width, this.options.height);
    
    // Smoke effects
    const smokeY = (this.cameraY * 0.4) % 300;
    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
    for (let i = 0; i < 6; i++) {
      const x = (i * 80 + Math.sin(this.cameraY * 0.01 + i) * 20) % this.options.width;
      const y = smokeY + i * 50;
      this.drawSmoke(x, y);
    }
  }
  
  private drawVolcanoBackground() {
    // Volcanic gradient
    const gradient = this.ctx.createLinearGradient(0, 0, 0, this.options.height);
    gradient.addColorStop(0, '#8B0000');
    gradient.addColorStop(0.3, '#FF0000');
    gradient.addColorStop(0.7, '#FF4500');
    gradient.addColorStop(1, '#FFD700');
    this.ctx.fillStyle = gradient;
    this.ctx.fillRect(0, 0, this.options.width, this.options.height);
    
    // Lava bubbles
    this.drawLavaBubbles();
  }
  
  private drawCloud(x: number, y: number) {
    this.ctx.beginPath();
    this.ctx.arc(x, y, 20, 0, Math.PI * 2);
    this.ctx.arc(x + 15, y, 25, 0, Math.PI * 2);
    this.ctx.arc(x + 30, y, 20, 0, Math.PI * 2);
    this.ctx.fill();
  }
  
  private drawSmoke(x: number, y: number) {
    this.ctx.beginPath();
    this.ctx.arc(x, y, 30, 0, Math.PI * 2);
    this.ctx.arc(x + 20, y - 10, 25, 0, Math.PI * 2);
    this.ctx.arc(x - 10, y - 5, 20, 0, Math.PI * 2);
    this.ctx.fill();
  }
  
  private drawLavaBubbles() {
    const time = Date.now() * 0.001;
    this.ctx.fillStyle = 'rgba(255, 69, 0, 0.7)';
    
    for (let i = 0; i < 10; i++) {
      const x = (i * 40 + Math.sin(time + i) * 20) % this.options.width;
      const y = this.options.height - 50 - Math.abs(Math.sin(time * 2 + i * 0.5)) * 100;
      const size = 10 + Math.sin(time * 3 + i) * 5;
      
      this.ctx.beginPath();
      this.ctx.arc(x, y, size, 0, Math.PI * 2);
      this.ctx.fill();
    }
  }
  
  private drawLevelIndicator() {
    // Level text
    this.ctx.save();
    this.ctx.font = 'bold 24px Arial';
    this.ctx.fillStyle = 'white';
    this.ctx.strokeStyle = 'black';
    this.ctx.lineWidth = 3;
    const levelText = `Level ${this.currentLevel}`;
    this.ctx.strokeText(levelText, 10, 50);
    this.ctx.fillText(levelText, 10, 50);
    this.ctx.restore();
  }
  
  private drawUI() {
    // Draw power-up indicators
    if (this.player.hasJetpack) {
      this.drawPowerUpIndicator('🚀', 10, 10);
    }
    if (this.player.hasShield) {
      this.drawPowerUpIndicator('🛡️', 60, 10);
    }
  }
  
  private drawPowerUpIndicator(emoji: string, x: number, y: number) {
    this.ctx.font = '24px Arial';
    this.ctx.fillText(emoji, x, y + 24);
  }
  
  private generateInitialPlatforms() {
    // Generate starting platforms
    for (let i = 0; i < 10; i++) {
      const x = Math.random() * (this.options.width - 80);
      const y = this.options.height - (i * 60) - 50;
      this.platforms.push(new Platform(x, y, 'normal'));
    }
  }
  
  private generatePlatforms() {
    // Find highest platform
    let highestY = this.options.height;
    this.platforms.forEach(platform => {
      if (platform.y < highestY) {
        highestY = platform.y;
      }
    });
    
    // Generate new platforms above
    while (highestY > this.cameraY - 200) {
      const x = Math.random() * (this.options.width - 80);
      const y = highestY - (50 + Math.random() * 50);
      
      // Difficulty increases with level and time
      const levelMultiplier = 1 + (this.currentLevel - 1) * 0.2;
      const timeMultiplier = 1 + Math.min(this.gameTime / 60, 2); // Max 3x difficulty after 2 minutes
      const difficultyMultiplier = levelMultiplier * timeMultiplier;
      
      // Randomly choose platform type (harder platforms more common at higher levels and time)
      const rand = Math.random();
      let type: PlatformType = 'normal';
      if (rand < 0.05 * difficultyMultiplier) type = 'fragile';
      else if (rand < 0.15 * difficultyMultiplier) type = 'moving';
      else if (rand < 0.2) type = 'spring';
      
      this.platforms.push(new Platform(x, y, type));
      
      // Chance to spawn power-up (less common at higher difficulty)
      if (Math.random() < 0.08 / Math.sqrt(difficultyMultiplier)) {
        const powerUpTypes: PowerUpType[] = ['jetpack', 'superJump', 'shield', 'balloon'];
        const powerUpType = powerUpTypes[Math.floor(Math.random() * powerUpTypes.length)];
        this.powerUps.push(new PowerUp(x + 40, y - 30, powerUpType));
      }
      
      // Chance to spawn enemy (more common at higher difficulty)
      if (Math.random() < 0.02 * difficultyMultiplier && this.height > 50) {
        const enemyTypes: ('bat' | 'windstorm' | 'lightning')[] = ['bat'];
        
        // Add harder enemies at higher levels
        if (this.currentLevel >= 3) enemyTypes.push('windstorm');
        if (this.currentLevel >= 5) enemyTypes.push('lightning');
        
        const enemyType = enemyTypes[Math.floor(Math.random() * enemyTypes.length)];
        this.enemies.push(new Enemy(x + 40, y - 50, enemyType));
      }
      
      highestY = y;
    }
  }
  
  private cleanup() {
    // Remove platforms that are too far below
    this.platforms = this.platforms.filter(
      platform => platform.y < this.cameraY + this.options.height + 100
    );
  }
  
  private gameOver() {
    this.isRunning = false;
    this.options.onGameOver();
  }
  
  private checkLevelProgression() {
    // Level thresholds
    const levelThresholds = [
      { level: 1, height: 0, theme: 'sky' as const },
      { level: 2, height: 200, theme: 'clouds' as const },
      { level: 3, height: 500, theme: 'space' as const },
      { level: 4, height: 1000, theme: 'fire' as const },
      { level: 5, height: 2000, theme: 'volcano' as const }
    ];
    
    // Find current level based on height
    for (let i = levelThresholds.length - 1; i >= 0; i--) {
      if (this.height >= levelThresholds[i].height) {
        if (this.currentLevel !== levelThresholds[i].level) {
          this.currentLevel = levelThresholds[i].level;
          this.levelTheme = levelThresholds[i].theme;
          
          // Show level up notification
          console.log(`Level ${this.currentLevel}! Theme: ${this.levelTheme}`);
        }
        break;
      }
    }
  }
  
  // Input handlers
  handleClick() {
    this.player.usePowerUp();
  }
  
  // Keyboard controls
  private handleKeyDown = (event: KeyboardEvent) => {
    if (!this.isRunning || this.isPaused) return;
    
    switch (event.key) {
      case 'ArrowLeft':
        this.player.moveLeft();
        break;
      case 'ArrowRight':
        this.player.moveRight();
        break;
      case ' ':
      case 'Space':
        this.player.usePowerUp();
        break;
    }
  };
  
  private handleKeyUp = (event: KeyboardEvent) => {
    if (!this.isRunning || this.isPaused) return;
    
    switch (event.key) {
      case 'ArrowLeft':
      case 'ArrowRight':
        this.player.stopMoving();
        break;
    }
  };
  
  private addKeyboardListeners() {
    window.addEventListener('keydown', this.handleKeyDown);
    window.addEventListener('keyup', this.handleKeyUp);
  }
  
  private removeKeyboardListeners() {
    window.removeEventListener('keydown', this.handleKeyDown);
    window.removeEventListener('keyup', this.handleKeyUp);
  }
  
  // Getters for statistics
  getPlayTime(): number {
    return Math.floor((Date.now() - this.startTime) / 1000);
  }
  
  getPowerUpsCollected(): number {
    return this.powerUpsCollected;
  }
  
  getEnemiesDefeated(): number {
    return this.enemiesDefeated;
  }
}