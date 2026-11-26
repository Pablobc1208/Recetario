import js from "@eslint/js";

export default [
  {
    ...js.configs.recommended,
    files: ["JS/**/*.js"],
    languageOptions: {
      ...js.configs.recommended.languageOptions,
      globals: {
        document: "readonly",
        window: "readonly"
      }
    }
  }
];
