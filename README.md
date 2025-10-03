# 🎵 Music AI - AI-Powered Music Discovery Platform

An intelligent music discovery platform that combines the power of AI (Claude & OpenAI) with Spotify's extensive catalog. Create personalized playlists or discover new albums and artists through natural language prompts.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat&logo=laravel)
![Livewire](https://img.shields.io/badge/Livewire-3-4E56A6?style=flat&logo=livewire)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-38B2AC?style=flat&logo=tailwind-css)
![Spotify](https://img.shields.io/badge/Spotify-API-1DB954?style=flat&logo=spotify)

## ✨ Features

### 🎼 Playlist Generator
Transform your musical ideas into curated Spotify playlists:
- **Natural Language Input**: "upbeat indie rock from the 2000s" → complete playlist
- **AI-Powered Curation**: Uses Claude or OpenAI to generate track lists
- **Spotify Integration**: Automatically creates and populates playlists
- **Smart Track Matching**: Validates and matches tracks with Spotify's catalog

### 🔍 Album & Artist Discovery
Discover new music through intelligent recommendations:
- **AI Recommendations**: Get album/artist suggestions based on prompts
- **Visual Discovery**: Beautiful album art and metadata
- **Availability Check**: See what's available on Spotify
- **One-Click Add**: Add selected albums directly to your Spotify library

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- Spotify Developer Account ([Create one](https://developer.spotify.com/dashboard))
- OpenAI API Key (optional, [Get one](https://platform.openai.com/api-keys))
- Claude API Key (optional, [Get one](https://console.anthropic.com/))

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/svrooman/vits-music-reco.git
   cd vits-music-reco
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up your API credentials in `.env`**
   ```env
   # Spotify API (Required)
   SPOTIFY_CLIENT_ID=your_spotify_client_id
   SPOTIFY_CLIENT_SECRET=your_spotify_client_secret
   SPOTIFY_REDIRECT_URI=http://localhost:8000/spotify/callback

   # AI Providers (At least one required)
   OPENAI_API_KEY=your_openai_api_key
   CLAUDE_API_KEY=your_claude_api_key
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

7. **Visit** `http://localhost:8000` 🎉

## 🎨 Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Livewire 3 + Flux UI
- **Styling**: Tailwind CSS (Dark minimalist theme)
- **Database**: SQLite (easily swappable)
- **Testing**: Pest PHP
- **APIs**:
  - Spotify Web API
  - OpenAI GPT-4o-mini
  - Anthropic Claude Sonnet

## 📸 Screenshots

### Playlist Generator
Create track-by-track playlists from natural language prompts.

### Discover
Find new albums and artists with AI-powered recommendations.

## 🔑 Getting Spotify API Credentials

1. Go to [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
2. Create a new app
3. Add `http://localhost:8000/spotify/callback` to Redirect URIs
4. Copy Client ID and Client Secret to your `.env` file

## 🛠️ Development

### Project Structure
```
app/
├── Http/Controllers/      # Request handlers
│   ├── PlaylistController.php
│   ├── DiscoverController.php
│   └── SpotifyController.php
├── Livewire/             # Livewire components
├── Services/             # Business logic
│   ├── SpotifyService.php
│   ├── PlaylistGeneratorService.php
│   └── AlbumRecommendationService.php
└── Models/               # Eloquent models

resources/views/
├── components/layouts/   # Layout components
├── playlist/             # Playlist views
├── discover/            # Discovery views
└── livewire/            # Livewire component views
```

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

## 🎯 Usage Examples

### Generate a Playlist
1. Click "Playlists" in the navigation
2. Enter a playlist name: "My Chill Vibes"
3. Enter inspiration: "relaxing lo-fi beats for studying"
4. Set number of tracks: 25
5. Click "Generate Playlist"

### Discover Albums
1. Click "Discover" in the navigation
2. Enter a prompt: "10 essential 90s hip hop albums"
3. Select type: "Albums"
4. Browse recommendations with album art
5. Select favorites and click "Add to Spotify"

## 🔧 Configuration

### AI Provider Selection
Edit `config/services.php` or set in `.env`:
```env
# Choose 'claude' or 'openai' (default: claude)
AI_PROVIDER=claude
```

### Playlist Defaults
Customize in `app/Services/PlaylistGeneratorService.php`:
- Max track duration
- Temperature (creativity)
- Validation settings

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP Framework
- [Livewire](https://livewire.laravel.com) - Reactive UI framework
- [Flux UI](https://fluxui.dev) - Beautiful UI components
- [Spotify Web API](https://developer.spotify.com/documentation/web-api) - Music catalog
- [OpenAI](https://openai.com) - AI recommendations
- [Anthropic Claude](https://anthropic.com) - AI recommendations

## 📬 Support

For support, please open an issue in the GitHub repository.

---

Built with ❤️ and 🤖 using Laravel, Livewire, and AI

**🤖 Generated with [Claude Code](https://claude.com/claude-code)**
