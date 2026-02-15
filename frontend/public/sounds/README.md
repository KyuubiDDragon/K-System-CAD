# Mail Notification Sound

This directory should contain the mail notification sound file.

## Required File

- **Filename**: `mail-notification.mp3`
- **Format**: MP3 audio file
- **Duration**: 1-3 seconds recommended
- **Volume**: Should be normalized (not too loud)

## Where to Get Sound Files

You can get free notification sounds from:

1. **Freesound** - https://freesound.org/
   - Search for "notification" or "mail"
   - Download and rename to `mail-notification.mp3`

2. **Zapsplat** - https://www.zapsplat.com/
   - Free sound effects library
   - Look for email/notification sounds

3. **Pixabay** - https://pixabay.com/sound-effects/
   - Free sound effects
   - Search for "notification bell" or "email"

4. **Create Your Own**
   - Use Audacity (free) to create/edit sounds
   - Export as MP3

## Example Sound

If no file is provided, the notification will still work but without sound.
The system will gracefully handle the missing file and log a message to the console.

## Installation

1. Download or create your notification sound
2. Rename it to `mail-notification.mp3`
3. Place it in this directory (`/frontend/public/sounds/`)
4. Restart the development server if needed

The sound will be played when:
- A new mail is received
- The user is logged in and connected to the mail socket
- Browser allows audio playback (may require user interaction first)

## Testing

To test the notification sound:
1. Send a test email to yourself
2. The notification should appear with sound
3. Check browser console if sound doesn't play (may need user interaction)
