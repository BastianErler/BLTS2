export type AppConfigResponse = {
    pwa?: {
        debug?: boolean;
        push_test?: boolean;
        vapid_public_key?: string | null;
        env?: string | null;
    };
};

function authHeaders() {
    const token = localStorage.getItem("auth_token");
    return token ? { Authorization: `Bearer ${token}` } : {};
}

export const appConfigApi = {
    async get() {
        const res = await fetch("/api/app-config", {
            headers: {
                ...authHeaders(),
                Accept: "application/json",
            },
        });

        if (!res.ok) {
            throw new Error(`app-config failed (${res.status})`);
        }

        return (await res.json()) as AppConfigResponse;
    },
};
