const API_BASE_URL = 'api/';

document.addEventListener('DOMContentLoaded', () => {
    const pageId = document.body.id;
    if (pageId === 'main-page') {
        initMainPage();
    } else if (pageId === 'viewer-page') {
        initViewerPage();
    }
});

// --- Main Page Logic (unchanged) ---
function initMainPage() {
    const contentGrid = document.getElementById('content-grid');
    const genreFilter = document.getElementById('genre-filter');
    const categoryLinks = document.querySelectorAll('.category-link');

    let currentCategory = 'Movies'; // Default category

    const fetchContent = async (category = 'Movies', genre = '', query = '') => {
        try {
            contentGrid.innerHTML = '<p>Loading...</p>';
            let url = `${API_BASE_URL}content.php?category=${encodeURIComponent(category)}&limit=50`;
            if (genre) {
                url += `&genre=${encodeURIComponent(genre)}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            let data = await response.json();

            if (query) {
                 data = data.filter(item => item.title.toLowerCase().includes(query.toLowerCase()));
            }

            renderContentGrid(data);

        } catch (error) {
            console.error("Failed to fetch content:", error);
            contentGrid.innerHTML = '<p>Error loading content. Please try again later.</p>';
        }
    };

    const renderContentGrid = (items) => {
        contentGrid.innerHTML = '';
        if (items.length === 0) {
            contentGrid.innerHTML = '<p>No content found.</p>';
            return;
        }
        items.forEach(item => {
            const card = document.createElement('a');
            card.href = `view.php?id=${item.id}`;
            card.className = 'content-card';
            card.innerHTML = `
                <img src="${item.poster}" alt="${item.title}" class="poster-img" loading="lazy">
                <div class="card-info">
                    <h4 class="card-title">${item.title}</h4>
                </div>
                <span class="card-tag">${item.category_name.replace(' Series', '')}</span>
            `;
            contentGrid.appendChild(card);
        });
    };

    genreFilter.addEventListener('change', () => {
        fetchContent(currentCategory, genreFilter.value, document.querySelector('.search-box input').value);
    });

    categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            currentCategory = e.target.dataset.category;
            document.getElementById('content-title').innerText = e.target.dataset.category;
            categoryLinks.forEach(l => l.classList.remove('active'));
            e.target.classList.add('active');
            fetchContent(currentCategory, genreFilter.value, document.querySelector('.search-box input').value);
        });
    });

    const searchInput = document.querySelector('.search-box input');
    searchInput.addEventListener('input', (e) => {
        fetchContent(currentCategory, genreFilter.value, e.target.value);
    });

    fetchContent(currentCategory);
}


// --- Viewer Page Logic (Updated with Player Logic) ---
function initViewerPage() {
    const params = new URLSearchParams(window.location.search);
    const contentId = params.get('id');

    if (!contentId) {
        document.getElementById('viewer-container').innerHTML = '<h1>Invalid Content ID</h1>';
        return;
    }

    const videoElement = document.getElementById('main-player');
    const iframeContainer = document.getElementById('iframe-container');
    let videoPlayer; // To hold the Video.js instance
    let shakaPlayer; // To hold the Shaka player instance
    let fullData; // To hold the fetched details data

    const fetchDetails = async () => {
        try {
            const response = await fetch(`${API_BASE_URL}details.php?id=${contentId}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            fullData = await response.json();
            renderDetails(fullData);
        } catch (error) {
            console.error("Failed to fetch details:", error);
            document.getElementById('viewer-container').innerHTML = '<h1>Error loading details.</h1>';
        }
    };

    const renderDetails = (data) => {
        document.title = `${data.title} - CineCraze`;
        document.getElementById('details-title').innerText = data.title;
        document.getElementById('details-meta').innerText = `${data.year} • ${data.genres.join(', ')} • ${data.rating}/10`;
        document.getElementById('details-description').innerText = data.description;

        if (data.category_name === 'TV Series') {
            const seasonsList = document.getElementById('seasons-list-container');
            seasonsList.innerHTML = '';
            data.seasons.forEach((season, index) => {
                const seasonEl = document.createElement('div');
                seasonEl.className = 'season-item';
                if (index === 0) seasonEl.classList.add('active');
                seasonEl.innerHTML = `<img src="${season.poster}" alt="Season ${season.season_number}"> <div>Season ${season.season_number}</div>`;
                seasonEl.addEventListener('click', () => {
                    document.querySelectorAll('.season-item').forEach(el => el.classList.remove('active'));
                    seasonEl.classList.add('active');
                    populateEpisodes(season.episodes);
                });
                seasonsList.appendChild(seasonEl);
            });

            if (data.seasons.length > 0) {
                populateEpisodes(data.seasons[0].episodes);
            }
        } else {
            document.getElementById('episode-select-container').style.display = 'none';
            document.getElementById('seasons-list').style.display = 'none';
            populateServers(data.servers);
        }
    };

    const populateEpisodes = (episodes) => {
        const episodeSelect = document.getElementById('episode-select');
        episodeSelect.innerHTML = '';
        episodes.forEach(episode => {
            const option = new Option(`E${episode.episode_number} - ${episode.title}`, episode.id);
            episodeSelect.add(option);
        });

        episodeSelect.onchange = () => {
            const selectedEpisode = episodes.find(ep => ep.id == episodeSelect.value);
            if (selectedEpisode) populateServers(selectedEpisode.servers);
        };

        if (episodes.length > 0) {
            populateServers(episodes[0].servers);
        }
    };

    const populateServers = (servers) => {
        const serverSelect = document.getElementById('server-select');
        serverSelect.innerHTML = '';
        if (!servers || servers.length === 0) {
            serverSelect.add(new Option('No servers available', ''));
            loadVideo(null);
            return;
        }
        servers.forEach(server => {
            const option = new Option(server.name, JSON.stringify(server));
            serverSelect.add(option);
        });

        serverSelect.onchange = () => {
            if (serverSelect.value) {
                loadVideo(JSON.parse(serverSelect.value));
            }
        };

        loadVideo(JSON.parse(serverSelect.value));
    };

    const loadVideo = (server) => {
        // --- Cleanup previous players ---
        if (videoPlayer) videoPlayer.dispose();
        if (shakaPlayer) shakaPlayer.destroy();
        iframeContainer.style.display = 'none';
        iframeContainer.innerHTML = '';
        videoElement.style.display = 'block';

        if (!server) {
            videoElement.style.display = 'none';
            return;
        }

        const isEmbed = server.url.includes('embed');

        if (server.is_drm == 1) {
            initShakaPlayer(server.url, server.license_url);
        } else if (isEmbed) {
            initIframePlayer(server.url);
        } else {
            initVideoJsPlayer(server.url);
        }
    };

    const initShakaPlayer = async (manifestUri, licenseServer) => {
        shaka.polyfill.installAll();
        if (!shaka.Player.isBrowserSupported()) {
            alert('Browser not supported for DRM content!');
            return;
        }

        shakaPlayer = new shaka.Player(videoElement);
        shakaPlayer.configure({
            drm: {
                servers: { 'com.widevine.alpha': licenseServer }
            }
        });

        try {
            await shakaPlayer.load(manifestUri);
            console.log('The video has been loaded!');
        } catch (e) {
            console.error('Error loading Shaka Player:', e);
        }
    };

    const initIframePlayer = (url) => {
        videoElement.style.display = 'none';
        iframeContainer.style.display = 'block';
        iframeContainer.innerHTML = `<iframe src="${url}" frameborder="0" allowfullscreen style="width:100%; height:100%;"></iframe>`;
    };

    const initVideoJsPlayer = (url) => {
        let type = '';
        if (url.endsWith('.m3u8')) {
            type = 'application/x-mpegURL';
        } else if (url.endsWith('.mpd')) {
            type = 'application/dash+xml';
        } else if (url.endsWith('.mp4')) {
            type = 'video/mp4';
        }

        videoPlayer = videojs(videoElement, {
            sources: [{ src: url, type: type }],
            autoplay: false,
            controls: true,
            fluid: true
        });
    };

    fetchDetails();
}
