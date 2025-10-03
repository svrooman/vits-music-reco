# CLAUDE.md - Project Context for AI Music Discovery Platform

## Project Overview
**vits-music-reco** is an AI-powered music discovery platform built with Laravel 12, Livewire 3, and Flux UI. It provides two main features:

1. **Playlist Generator** (`/`) - Create track-by-track Spotify playlists from AI prompts
2. **Discover** (`/discover`) - Get album/artist recommendations with AI, check availability, and add to Spotify library

## Tech Stack
- **Framework**: Laravel 12
- **Frontend**: Livewire 3 + Flux UI components
- **Styling**: Tailwind CSS (dark/minimalist theme)
- **Database**: SQLite
- **Testing**: Pest PHP
- **APIs**: Spotify Web API, OpenAI GPT-4o-mini, Claude Sonnet

## Project Structure

### Key Directories
```
app/
├── Http/Controllers/
│   ├── PlaylistController.php      # Main playlist view
│   ├── DiscoverController.php      # Album/artist discovery
│   ├── SpotifyController.php       # Spotify OAuth
│   └── Api/
│       └── PlaylistController.php  # API for playlist generation
├── Livewire/Forms/
│   └── PlaylistGenerator.php      # Livewire component for playlist form
├── Models/
│   ├── User.php
│   └── Playlist.php
└── Services/
    ├── SpotifyService.php                  # Spotify Web API integration
    ├── ChatGptService.php                  # OpenAI API (used for track generation)
    ├── PlaylistGeneratorService.php        # Main playlist generation logic
    ├── AlbumRecommendationService.php      # Album/artist discovery logic
    ├── LocalLibraryService.php             # (Optional) Local music library integration
    └── LocalLibraryRagService.php          # (Optional) RAG-based recommendations

resources/views/
├── components/layouts/
│   └── music.blade.php              # Main minimalist layout
├── playlist/
│   └── index.blade.php              # Playlist generator page
├── discover/
│   └── index.blade.php              # Discover page
└── livewire/forms/
    └── playlist-generator.blade.php # Playlist form component
```

### Routes (`routes/web.php`)
```php
// Playlists
GET  /                                  -> PlaylistController@index
GET  /playlist/{id}                     -> PlaylistController@show
POST /playlist/generate-and-create      -> Api\PlaylistController@generateAndCreate

// Discover
GET  /discover                          -> DiscoverController@index
POST /discover/generate                 -> DiscoverController@generate
POST /discover/add-to-library          -> DiscoverController@addToLibrary

// Spotify OAuth
GET  /spotify/auth                      -> SpotifyController@redirectToSpotify
GET  /spotify/callback                  -> SpotifyController@handleSpotifyCallback
GET  /spotify/logout                    -> SpotifyController@logout
```

## Key Features

### 1. Playlist Generator
- **Input**: Natural language prompt (e.g., "upbeat indie rock from the 2000s")
- **Process**:
  1. User enters inspiration text, playlist name, # of tracks
  2. ChatGPT/Claude generates track list
  3. SpotifyService searches for each track
  4. Creates Spotify playlist and adds tracks
  5. Saves to database
- **Output**: Spotify playlist link + saved record

### 2. Discover (Album/Artist Discovery)
- **Input**: Natural language prompt (e.g., "10 contemporary English jazz albums")
- **Process**:
  1. User enters discovery prompt
  2. AlbumRecommendationService uses AI to generate recommendations
  3. Enriches with Spotify data (album art, availability)
  4. Displays as interactive checklist
  5. User selects items and adds to Spotify library
- **Output**: Album recommendations with art + Spotify links

## Service Classes Explained

### SpotifyService
- Handles Spotify OAuth (access tokens stored in session)
- Methods:
  - `createPlaylist()` - Creates Spotify playlist
  - `getTrackIds()` - Searches for tracks
  - `addTracksToPlaylist()` - Adds tracks to playlist
  - Token refresh logic built-in

### PlaylistGeneratorService
- Generates track-by-track playlists
- Supports multiple AI providers (Claude, OpenAI)
- Methods:
  - `generatePlaylist()` - Main entry point
  - `generateWithClaude()` / `generateWithOpenAI()` - AI-specific logic
  - `validateTracksWithSpotify()` - Checks track availability
  - Detects inspiration type (artist-song, genre, mood, era)

### AlbumRecommendationService
- Generates album/artist recommendations
- Methods:
  - `getRecommendations()` - Main entry point
  - `enrichWithSpotifyData()` - Fetches album art, availability
  - `addToSpotifyLibrary()` - Adds selected albums to user's library

## Configuration

### Environment Variables (`.env`)
```bash
# Spotify API
SPOTIFY_CLIENT_ID=your_client_id
SPOTIFY_CLIENT_SECRET=your_client_secret
SPOTIFY_REDIRECT_URI=http://localhost:8000/spotify/callback
SPOTIFY_API_URL=https://api.spotify.com/v1

# AI Providers
OPENAI_API_KEY=your_openai_key
CLAUDE_API_KEY=your_claude_key

# Optional: Local Music Library
LOCAL_LIBRARY_API_URL=http://minipc.local:8888
LOCAL_LIBRARY_RAG_API_URL=http://minipc.local:8889
```

### Services Config (`config/services.php`)
All API credentials are pulled from `.env` via the services config.

## UI/UX Design Philosophy

### Minimalist Dark Theme
- **Colors**: Black (`bg-black`), Zinc grays (`zinc-800`, `zinc-900`)
- **Accents**: Purple-to-blue gradients (`from-purple-500 to-blue-500`)
- **Typography**: Inter font, clean spacing
- **Components**: Flux UI components (inputs, buttons, cards)
- **Layout**: Sticky header, centered content (max-w-7xl), card-based design

### Navigation
- **Header**: Logo + Playlists/Discover tabs + Spotify auth
- **Layout**: Single minimalist layout (`music.blade.php`)
- **Spotify Auth**: Shows user profile when connected

## Database Schema

### `playlists` table
```sql
- id (bigint, primary key)
- user_id (string) - Spotify user ID or UUID
- name (string) - Playlist name
- description (text) - User's inspiration prompt
- spotify_playlist_id (string) - Spotify playlist ID
- spotify_playlist_uri (string) - spotify:playlist:xxx
- tracks (json) - Array of track objects {artist, track, album}
- created_at, updated_at
```

### `users` table
- Standard Laravel Fortify user table
- Added `spotify_id` column for Spotify integration

## Development Workflow

### Starting the App
```bash
cd /home/mrnegitoro/apps/vits/vits-music-reco
php artisan serve
```

### Running Migrations
```bash
php artisan migrate
```

### Testing
```bash
php artisan test  # Uses Pest
```

### Livewire Development
- Components are in `app/Livewire/`
- Views are in `resources/views/livewire/`
- Use `wire:model` for two-way binding
- Use `wire:submit` for form handling

## Common Tasks

### Adding a New Feature
1. Create controller: `php artisan make:controller FeatureController`
2. Add routes in `routes/web.php`
3. Create view in `resources/views/feature/`
4. Use Flux components for UI consistency

### Creating a Livewire Component
```bash
php artisan make:livewire ComponentName
```

### Debugging
- Use `dd()` or `logger()->info()` for debugging
- Check `storage/logs/laravel.log`
- Livewire errors appear in browser console

## Known Gotchas

1. **Spotify Tokens**: Stored in session, not persisted to DB (refreshed automatically)
2. **User IDs**: Uses Spotify user ID if authenticated, otherwise generates UUID
3. **AI Rate Limits**: OpenAI/Claude have rate limits - handle gracefully
4. **Track Matching**: Spotify search isn't perfect - uses fuzzy matching with similarity scores
5. **Flux Components**: Use `flux:` prefix (e.g., `<flux:button>`, `<flux:input>`)

## Git Workflow
- **Repo**: https://github.com/svrooman/vits-music-reco
- **Branch**: `main`
- Always commit with descriptive messages
- Include "🤖 Generated with Claude Code" in commits when AI-assisted

## Future Enhancements (Ideas)
- [ ] Apple Music integration
- [ ] Tidal integration
- [ ] User authentication (currently uses Spotify OAuth only)
- [ ] Playlist sharing
- [ ] Collaborative playlists
- [ ] Advanced filters (decade, BPM, mood)
- [ ] Playlist versioning/history
- [ ] Export to other platforms

## Support & Documentation
- Laravel Docs: https://laravel.com/docs/12.x
- Livewire Docs: https://livewire.laravel.com
- Flux UI Docs: https://fluxui.dev
- Spotify API Docs: https://developer.spotify.com/documentation/web-api

---

**Last Updated**: 2025-10-02
**Working Directory**: `/home/mrnegitoro/apps/vits/vits-music-reco`
