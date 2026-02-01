import { fileURLToPath } from "url";
import { dirname } from "path";

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

/** @type {import('tailwindcss').Config} */
export default {
    content: ["./index.html", "./resources/vue/src/**/*.{vue,js,ts,jsx,tsx}"],
    theme: {
        extend: {
            colors: {
                navy: {
                    50: "#f0f4f8",
                    100: "#d9e2ec",
                    200: "#bcccdc",
                    300: "#9fb3c8",
                    400: "#829ab1",
                    500: "#627d98",
                    600: "#486581",
                    700: "#334e68",
                    800: "#243b53",
                    900: "#1e2938",
                    950: "#0f1419",
                },
                bordeaux: {
                    50: "#fef2f3",
                    100: "#fde6e7",
                    200: "#fbd0d5",
                    300: "#f7aab2",
                    400: "#f27a8a",
                    500: "#e74c60",
                    600: "#d42e45",
                    700: "#b22234",
                    800: "#8b2332",
                    900: "#7a1f2d",
                    950: "#450d16",
                },
            },
            fontFamily: {
                sans: ["Inter", "system-ui", "sans-serif"],
                display: ["Bebas Neue", "Impact", "sans-serif"],
            },
        },
    },
    plugins: [],
};
