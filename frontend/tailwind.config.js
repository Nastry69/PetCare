/** @type {import('tailwindcss').Config} */
export default {
  content: ["./src/**/*.{js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        primary: "#1377EC",
        success: "#22C55E",
        warning: "#F59E0B",
        danger: "#EF4444",
        dark: "#0F172A",
        secondary: "#475569",
        light: "#F8FAFC"
      }
    },
  },
  plugins: [],
}

