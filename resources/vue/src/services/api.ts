import axios from "axios";

const baseURL = import.meta.env.VITE_API_URL;
if (!baseURL) {
    throw new Error("VITE_API_URL is not set");
}

const api = axios.create({
    baseURL,
    withCredentials: false,
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
    },
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error?.response?.status;

        if (status === 401) {
            localStorage.removeItem("auth_token");

            if (window.location.pathname !== "/login") {
                window.location.href = "/login";
            }
        }

        return Promise.reject(error);
    },
);

// Add auth token to requests
api.interceptors.request.use((config) => {
    const token = localStorage.getItem("auth_token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export interface MeResponse {
    id: number;
    name: string;
    email: string;
    mobile: string | null;
    is_admin: boolean;
    balance: string | number;
    jokers_remaining: number;
    jokers_used: any[];
    wants_email_reminder: boolean | number;
    wants_sms_reminder: boolean | number;
    bet_count: number;
}

export interface Team {
    id: number;
    name: string;
    short_name: string;
    logo_url: string | null;
}

export interface Game {
    id: number;
    match_number: number;
    opponent: Team;
    is_home: boolean;
    kickoff_at: string;
    eisbaeren_goals: number | null;
    opponent_goals: number | null;
    status: "scheduled" | "live" | "finished";
    is_derby: boolean;
    is_playoff: boolean;
    can_bet: boolean;
    bet_deadline_at: string;
    winner?: "eisbaeren" | "opponent" | "draw" | null;
}

export interface Bet {
    id: number;
    game_id: number;
    eisbaeren_goals: number;
    opponent_goals: number;
    joker_type: string | null;
    base_price: number;
    multiplier: number;
    final_price: number;
    locked_at: string | null;
    game: Game;
}

export interface UserStats {
    user: {
        id: number;
        name: string;
        balance: number;
        jokers_remaining: number;
        jokers_used?: any[];
    };
    season: {
        id: number;
        name: string;
    };
    total_cost: number;
    bet_count: number;
    exact_bets: number;
    tendency_bets: number;
    winner_bets: number;
    wrong_bets: number;
    jokers_used?: any[];
    position: number;
}

export interface LeaderboardEntry {
    id: number;
    name: string;
    total_cost: number;
    bet_count: number;
    exact_bets: number;
    average_cost: number;
    jokers_remaining: number;
    rank: number;
    delta: number;
    is_me: boolean;
}

export interface LeaderboardResponse {
    season: {
        id: number;
        name: string;
    };
    delta_basis?: {
        latest_finished_game_id?: number | null;
        previous_finished_game_id?: number | null;
        latest_cutoff?: string | null;
        previous_cutoff?: string | null;
    };
    me: LeaderboardEntry | null;
    top3: LeaderboardEntry[];
    entries: LeaderboardEntry[];
    generated_at: string;
}

export interface Season {
    id: number;
    name: string;
    is_active: boolean;
    start_date: string;
    end_date: string | null;
}

export type NotificationSettings = {
    v?: number;
    push_enabled: boolean;
    remind_before_deadline: boolean;
    remind_before_deadline_minutes: number;
    remind_on_game_start_if_no_bet: boolean;
    notify_on_bet_result: boolean;
    notify_on_rank_change: boolean;
    rank_change_threshold: number;
};

export const notificationSettingsApi = {
    get: () => api.get<NotificationSettings>("/notification-settings"),
    update: (data: Partial<NotificationSettings>) =>
        api.patch<NotificationSettings>("/notification-settings", data),
};

// Games API
export const gamesApi = {
    getAll: (params?: {
        status?: string;
        upcoming?: boolean;
        past?: boolean;
    }) => api.get<{ data: Game[] }>("/games", { params }),

    getUpcoming: (limit = 5) => api.get<{ data: Game[] }>("/games/upcoming"),

    getOne: (id: number) => api.get<{ data: Game }>(`/games/${id}`),

    getUserBet: (gameId: number) => api.get(`/games/${gameId}/user-bet`),
};

// Bets API
export const betsApi = {
    getAll: () => api.get<{ data: Bet[] }>("/bets"),

    create: (data: {
        game_id: number;
        eisbaeren_goals: number;
        opponent_goals: number;
        joker_type?: string;
    }) => api.post<{ data: Bet }>("/bets", data),

    update: (
        id: number,
        data: { eisbaeren_goals: number; opponent_goals: number },
    ) => api.put<{ data: Bet }>(`/bets/${id}`, data),

    delete: (id: number) => api.delete(`/bets/${id}`),
};

// Leaderboard API
export const leaderboardApi = {
    get: (params?: { season_id?: number }) =>
        api.get<LeaderboardResponse>("/leaderboard", { params }),

    // FIX: Stats endpoints hängen unter /leaderboard/user-stats...
    getUserStats: (userId?: number, params?: { season_id?: number }) =>
        api.get<UserStats>(
            userId
                ? `/leaderboard/user-stats/${userId}`
                : "/leaderboard/user-stats",
            { params },
        ),
};

// Auth API
export const authApi = {
    login: (email: string, password: string) =>
        api.post("/login", { email, password }),

    logout: () => api.post("/logout"),

    me: () => api.get<MeResponse>("/me"),

    getMe: () => api.get<MeResponse>("/me"),
};

export const seasonsApi = {
    getAll: () => api.get<{ data: Season[] }>("/seasons"),
};

export const getLogoUrl = (logoPath: string | null): string => {
    const apiUrl = (import.meta.env.VITE_API_URL ?? "").replace(/\/$/, "");
    const backendOrigin =
        apiUrl.replace(/\/api(?:\/.*)?$/i, "") || window.location.origin;

    if (!logoPath) return `${backendOrigin}/images/team-placeholder.png`;

    // already absolute
    if (logoPath.startsWith("http://") || logoPath.startsWith("https://")) {
        return logoPath;
    }

    // "/storage/..." oder "/images/..." etc. -> Backend-Origin davor
    if (logoPath.startsWith("/")) {
        return `${backendOrigin}${logoPath}`;
    }

    // "storage/..." -> Backend-Origin davor
    if (logoPath.startsWith("storage/")) {
        return `${backendOrigin}/${logoPath}`;
    }

    // "logos/1.svg" -> /storage/logos/1.svg
    if (logoPath.startsWith("logos/")) {
        return `${backendOrigin}/storage/${logoPath}`;
    }

    // "1.svg" -> /storage/logos/1.svg
    return `${backendOrigin}/storage/logos/${logoPath}`;
};

export default api;
