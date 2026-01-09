module.exports = {
  root: true,
  env: {
    browser: true,
    es2022: true,
  },
  parserOptions: {
    ecmaVersion: 2022,
    sourceType: "module",
  },
  settings: {
    react: {
      version: "detect",
    },
  },
  plugins: ["react", "react-hooks"],
  extends: ["eslint:recommended", "plugin:react/recommended", "plugin:react-hooks/recommended"],
  rules: {
    "react/prop-types": "off",
    "no-console": "warn", // Warn about console usage - use logger utility instead
  },
  overrides: [
    {
      files: ["**/utils/logger.js"],
      rules: {
        "no-console": "off", // Logger utility is allowed to use console
      },
    },
  ],
};
