document.addEventListener('DOMContentLoaded', () => {
    // --- Player Elements ---
    const shakaPlayerElement = document.getElementById('shaka-player');
    const iframePlayerElement = document.getElementById('iframe-player');
    const noPlayerMessage = document.getElementById('no-player-message');
    let shakaPlayer = null;

    // --- Selector Elements ---
    const serverSelect = document.getElementById('server-select');
    const seasonSelect = document.getElementById('season-select');
    const episodeSelect = document.getElementById('episode-select');

    // --- Initialize Shaka Player ---
    function initShakaPlayer() {
        if (shakaPlayer) return; // Already initialized
        shaka.polyfill.installAll();
        if (shaka.Player.isBrowserSupported()) {
            shakaPlayer = new shaka.Player(shakaPlayerElement);
            shakaPlayer.addEventListener('error', onPlayerErrorEvent);
        } else {
            console.error('Browser not supported by Shaka Player.');
        }
    }

    function onPlayerErrorEvent(errorEvent) {
        console.error('Shaka Player Error:', errorEvent.detail);
    }

    // --- Smart Player Loader ---
    function loadPlayer() {
        const selectedServer = serverSelect.options[serverSelect.selectedIndex];
        if (!selectedServer) {
            showPlayer('none');
            return;
        }

        const url = selectedServer.value;
        const isMpd = url.toLowerCase().endsWith('.mpd');
        const isM3u8 = url.toLowerCase().endsWith('.m3u8');
        const licenseUrl = selectedServer.dataset.license || '';

        if (isMpd || isM3u8) {
            showPlayer('shaka');
            initShakaPlayer();
            if (shakaPlayer) {
                const config = { drm: {} };
                if (licenseUrl) {
                    // This assumes Widevine for .mpd with license. A more robust solution
                    // would check for FairPlay, etc.
                    config.drm.servers = { 'com.widevine.alpha': licenseUrl };
                }
                shakaPlayer.configure(config);
                shakaPlayer.load(url).catch(onPlayerErrorEvent);
            }
        } else if (url) {
            showPlayer('iframe');
            iframePlayerElement.src = url;
        } else {
            showPlayer('none');
        }
    }

    function showPlayer(type) {
        shakaPlayerElement.style.display = (type === 'shaka') ? 'block' : 'none';
        iframePlayerElement.style.display = (type === 'iframe') ? 'block' : 'none';
        noPlayerMessage.style.display = (type === 'none') ? 'block' : 'none';
    }

    // --- Event Listeners ---
    if (serverSelect) {
        serverSelect.addEventListener('change', loadPlayer);
    }

    if (seasonSelect && episodeSelect && typeof episodesBySeason !== 'undefined') {
        seasonSelect.addEventListener('change', () => {
            const selectedSeasonId = seasonSelect.value;
            const episodes = episodesBySeason[selectedSeasonId] || [];
            episodeSelect.innerHTML = '';

            if (episodes.length > 0) {
                episodes.forEach(episode => {
                    const option = document.createElement('option');
                    option.value = episode.id;
                    option.textContent = `Ep ${episode.episode_number}: ${episode.title}`;
                    episodeSelect.appendChild(option);
                });
            } else {
                episodeSelect.innerHTML = '<option>No episodes found</option>';
            }
            episodeSelect.dispatchEvent(new Event('change'));
        });
    }

    if (episodeSelect) {
        episodeSelect.addEventListener('change', () => {
            const selectedEpisodeId = episodeSelect.value;
            if (!selectedEpisodeId) {
                loadPlayer(); // Will show 'no player' message
                return;
            }

            fetch(`api/get_servers.php?episode_id=${selectedEpisodeId}`)
                .then(response => response.json())
                .then(servers => {
                    serverSelect.innerHTML = '';
                    if (servers && servers.length > 0) {
                        servers.forEach(server => {
                            const option = document.createElement('option');
                            option.value = server.url;
                            option.textContent = server.name;
                            if(server.license_url) {
                                option.dataset.license = server.license_url;
                            }
                            serverSelect.appendChild(option);
                        });
                    } else {
                        serverSelect.innerHTML = '<option value="">No servers available</option>';
                    }
                    loadPlayer(); // Load the first server for the new episode
                })
                .catch(error => {
                    console.error('Error fetching servers:', error);
                    serverSelect.innerHTML = '<option>Error loading servers</option>';
                    loadPlayer();
                });
        });
    }

    // --- Initial Load ---
    loadPlayer();
});
