<?php
class TMDB_API {
    private $api_key_primary = 'ec926176bf467b3f7735e3154238c161';
    private $api_key_backup = 'bb51e18edb221e87a05f90c2eb456069';
    private $api_base_url = 'https://api.themoviedb.org/3/';
    private $image_base_url = 'https://image.tmdb.org/t/p/';

    private function do_curl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CineCrazeApp/1.0');
        $output = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            // Simple error handling: return null if API call fails
            return null;
        }

        return json_decode($output, true);
    }

    public function search($query, $type = 'movie') {
        $url = $this->api_base_url . "search/{$type}?api_key=" . $this->api_key_primary . "&query=" . urlencode($query);
        $data = $this->do_curl($url);
        return $data ? $data['results'] : [];
    }

    public function get_movie_details($tmdb_id) {
        $url = $this->api_base_url . "movie/{$tmdb_id}?api_key=" . $this->api_key_primary;
        $details = $this->do_curl($url);
        if (!$details) return null;

        return [
            'title' => $details['title'],
            'description' => $details['overview'],
            'poster' => $this->image_base_url . 'w500' . $details['poster_path'],
            'year' => substr($details['release_date'], 0, 4),
            'rating' => $details['vote_average'],
            'tmdb_id' => $details['id']
            // other fields can be added here
        ];
    }

    public function get_tv_show_details($tmdb_id) {
        // Fetch main show details
        $show_url = $this->api_base_url . "tv/{$tmdb_id}?api_key=" . $this->api_key_primary;
        $details = $this->do_curl($show_url);
        if (!$details) return null;

        $show_data = [
            'title' => $details['name'],
            'description' => $details['overview'],
            'poster' => $this->image_base_url . 'w500' . $details['poster_path'],
            'year' => substr($details['first_air_date'], 0, 4),
            'rating' => $details['vote_average'],
            'tmdb_id' => $details['id'],
            'seasons' => []
        ];

        // Fetch each season's details
        foreach ($details['seasons'] as $season) {
            if ($season['season_number'] == 0) continue; // Skip "Specials"

            $season_url = $this->api_base_url . "tv/{$tmdb_id}/season/{$season['season_number']}?api_key=" . $this->api_key_primary;
            $season_details = $this->do_curl($season_url);
            if (!$season_details) continue;

            $season_data = [
                'season_number' => $season_details['season_number'],
                'poster' => $this->image_base_url . 'w500' . $season_details['poster_path'],
                'episodes' => []
            ];

            foreach ($season_details['episodes'] as $episode) {
                $season_data['episodes'][] = [
                    'episode_number' => $episode['episode_number'],
                    'title' => $episode['name'],
                    'description' => $episode['overview'],
                    'thumbnail' => $this->image_base_url . 'w500' . $episode['still_path']
                ];
            }
            $show_data['seasons'][] = $season_data;
        }
        return $show_data;
    }
}
?>
