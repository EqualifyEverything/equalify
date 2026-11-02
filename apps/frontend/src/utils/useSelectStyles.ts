import themeVariables from "../global-styles/variables.module.scss";
import { useGlobalStore } from "./useGlobalStore";

// Shared react-select theming (BlockersTable filters, audit tag inputs)
export const useSelectStyles = () => {
  const { darkMode } = useGlobalStore();

  // Matches the resting (unfocused) look of the plain <select> filters
  // (global-styles/inputs.scss + selects.scss for light mode, dark.scss's
  // `body.dark select` for dark mode) so the two react-select multiboxes
  // read as the same kind of control, not a visually distinct widget.
  // Focus states are deliberately left as react-select's own
  // border/box-shadow (`base.borderColor`/`base.boxShadow`) rather than
  // reimplemented here — overriding those for every state would risk
  // silently weakening the focus indicator keyboard users rely on.
  const selectBg = darkMode ? themeVariables.dark_surface : themeVariables.white;
  const selectBorder = darkMode ? themeVariables.dark_border : themeVariables.gray;
  const selectText = darkMode ? themeVariables.paper : themeVariables.black;
  return {
    control: (base: any, state: any) => ({
      ...base,
      backgroundColor: selectBg,
      borderColor: state.isFocused ? base.borderColor : selectBorder,
      borderRadius: `calc(${themeVariables.spacing} / 2)`,
      boxShadow: state.isFocused ? base.boxShadow : themeVariables["shadow-inset"],
      color: selectText,
      fontSize: "16px",
      cursor: "pointer",
    }),
    menu: (base: any) => ({
      ...base,
      backgroundColor: selectBg,
      borderColor: selectBorder,
    }),
    option: (base: any, state: any) => ({
      ...base,
      backgroundColor: state.isSelected
        ? (darkMode ? themeVariables.black : themeVariables.gray)
        : state.isFocused
          ? (darkMode ? themeVariables.dark_border : themeVariables.paper)
          : "transparent",
      color: selectText,
    }),
    multiValue: (base: any) => ({
      ...base,
      backgroundColor: darkMode ? themeVariables.dark_border : themeVariables.paper,
    }),
    multiValueLabel: (base: any) => ({
      ...base,
      color: selectText,
    }),
    multiValueRemove: (base: any) => ({
      ...base,
      color: selectText,
      ":hover": {
        backgroundColor: darkMode ? themeVariables.black : themeVariables.gray,
        color: selectText,
      },
    }),
    placeholder: (base: any) => ({
      ...base,
      color: selectText,
      opacity: 0.5,
    }),
    singleValue: (base: any) => ({
      ...base,
      color: selectText,
    }),
    input: (base: any) => ({
      ...base,
      color: selectText,
    }),
    indicatorSeparator: (base: any) => ({
      ...base,
      backgroundColor: selectBorder,
    }),
    dropdownIndicator: (base: any) => ({
      ...base,
      color: selectText,
      padding: "4px",
      cursor: "pointer",
    }),
    clearIndicator: (base: any) => ({
      ...base,
      color: selectText,
      opacity: 0.6,
      padding: "4px",
    }),
  };
};
