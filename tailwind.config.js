import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/**
 * Tema basado en DESIGN.md (Core Assets System):
 * paleta corporativa azul, tokens de superficie tipo Material,
 * tipografía Inter con escala compacta y radios de 8px.
 */

/** @type {import('tailwindcss').Config} */
export default {
    presets: [require("./vendor/wireui/wireui/tailwind.config.js")],
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./vendor/wireui/wireui/src/*.php",
        "./vendor/wireui/wireui/ts/**/*.ts",
        "./vendor/wireui/wireui/src/WireUi/**/*.php",
        "./vendor/wireui/wireui/src/Components/**/*.php",
        "./node_modules/flowbite/**/*.js",
        "./vendor/rappasoft/laravel-livewire-tables/resources/views/**/*.blade.php",
        "./app/Livewire/**/*.php",
        "./app/Support/**/*.php",
    ],

    // Clases generadas dinámicamente (badges de colores de catálogos)
    safelist: [
        { pattern: /(bg|text)-(green|blue|indigo|yellow|red|gray)-(50|100|600|800)/ },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Design tokens — DESIGN.md (Core Assets System)
                surface: "#faf8ff",
                "surface-dim": "#d9d9e4",
                "surface-bright": "#faf8ff",
                "surface-container-lowest": "#ffffff",
                "surface-container-low": "#f3f3fd",
                "surface-container": "#ededf8",
                "surface-container-high": "#e7e7f2",
                "surface-container-highest": "#e1e2ec",
                "surface-variant": "#e1e2ec",
                "on-surface": "#191b23",
                "on-surface-variant": "#434654",
                "inverse-surface": "#2e3038",
                "inverse-on-surface": "#f0f0fb",
                outline: "#737685",
                "outline-variant": "#c3c6d6",
                canvas: "#F4F5F7", // lienzo principal de la aplicación
                "border-soft": "#DFE1E6", // borde de cards / inputs
                // primary y secondary como paletas completas: WireUI hace
                // @apply de tonos (p.ej. bg-secondary-200), no pueden ser un solo hex.
                primary: {
                    DEFAULT: "#003d9b",
                    50: "#eef4ff",
                    100: "#dae2ff",
                    200: "#b2c5ff",
                    300: "#8aa8ff",
                    400: "#5c82f2",
                    500: "#0052cc",
                    600: "#0047b8",
                    700: "#003d9b",
                    800: "#00307d",
                    900: "#001848",
                },
                "on-primary": "#ffffff",
                "primary-container": "#0052cc",
                "on-primary-container": "#c4d2ff",
                "primary-fixed": "#dae2ff",
                "primary-fixed-dim": "#b2c5ff",
                secondary: {
                    DEFAULT: "#285ab9",
                    50: "#f3f6ff",
                    100: "#d9e2ff",
                    200: "#b1c6ff",
                    300: "#8fabf2",
                    400: "#5c82d9",
                    500: "#285ab9",
                    600: "#1f4da3",
                    700: "#173f8a",
                    800: "#0f3170",
                    900: "#001946",
                },
                "on-secondary": "#ffffff",
                "secondary-container": "#709bfe",
                "on-secondary-container": "#003179",
                tertiary: "#7b2600",
                "on-tertiary": "#ffffff",
                "tertiary-container": "#a33500",
                "on-tertiary-container": "#ffc6b2",
                error: "#ba1a1a",
                "on-error": "#ffffff",
                "error-container": "#ffdad6",
                "on-error-container": "#93000a",
                success: "#10B981",
                alert: "#E11D48",
            },
            fontSize: {
                "display-lg": ["30px", { lineHeight: "38px", letterSpacing: "-0.02em", fontWeight: "700" }],
                "headline-md": ["24px", { lineHeight: "32px", letterSpacing: "-0.01em", fontWeight: "600" }],
                "headline-sm": ["20px", { lineHeight: "28px", fontWeight: "600" }],
                "title-md": ["16px", { lineHeight: "24px", fontWeight: "600" }],
                "body-md": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                "body-sm": ["13px", { lineHeight: "18px", fontWeight: "400" }],
                "label-md": ["12px", { lineHeight: "16px", fontWeight: "600" }],
                "mono-sm": ["13px", { lineHeight: "18px", letterSpacing: "0.02em", fontWeight: "400" }],
            },
            spacing: {
                unit: "4px",
                "container-padding": "24px",
                gutter: "16px",
                "stack-sm": "8px",
                "stack-md": "16px",
                "table-cell-padding": "12px",
            },
        },
    },

    plugins: [forms, typography, require("flowbite/plugin")],
};
