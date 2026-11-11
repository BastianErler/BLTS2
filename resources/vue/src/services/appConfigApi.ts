import api from "@/services/api";

export type AppConfigResponse = {
    pwa?: {
        debug?: boolean;
        push_test?: boolean;
        vapid_public_key?: string | null;
        env?: string | null;
    };
};

export const appConfigApi = {
    async get() {
        const response = await api.get<AppConfigResponse>("/app-config");
        return response.data;
    },
};
