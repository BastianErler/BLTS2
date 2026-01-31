/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/vue/index.html",
        "./resources/vue/src/**/*.{vue,js,ts,jsx,tsx}",
        "./resources/**/*.{vue,js,ts,jsx,tsx}",
    ],
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
                ice: {
                    50: "#f0f9ff",
                    100: "#e0f2fe",
                    200: "#b9e5fe",
                    300: "#7dd3fc",
                    400: "#38bdf8",
                    500: "#0ea5e9",
                    600: "#0284c7",
                    700: "#0369a1",
                    800: "#075985",
                    900: "#0c4a6e",
                },
            },
            fontFamily: {
                sans: ["Inter", "system-ui", "sans-serif"],
                display: ["Bebas Neue", "Impact", "sans-serif"],
            },
            backgroundImage: {
                "ice-texture": "url('/images/ice-texture.png')",
                "gradient-radial": "radial-gradient(var(--tw-gradient-stops))",
            },
            boxShadow: {
                glow: "0 0 20px rgba(14, 165, 233, 0.5)",
                "glow-red": "0 0 20px rgba(139, 35, 50, 0.5)",
            },
            animation: {
                "pulse-slow": "pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite",
                float: "float 3s ease-in-out infinite",
            },
            keyframes: {
                float: {
                    "0%, 100%": { transform: "translateY(0)" },
                    "50%": { transform: "translateY(-10px)" },
                },
            },
        },
    },
    plugins: [],
};
