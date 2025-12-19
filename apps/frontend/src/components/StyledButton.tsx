import { ReactNode } from "react";
import styles from "./StyledButton.module.scss";
import * as AccessibleIcon from "@radix-ui/react-accessible-icon";
import * as VisuallyHidden from "@radix-ui/react-visually-hidden";

interface ButtonProps extends React.PropsWithChildren {
  variant?: string;
  icon?: ReactNode;
  onClick: (e?:any) => Promise<void> | void;
  label: string;
  showLabel?: boolean;
  disabled?: boolean;
  loading?: boolean;
  className?: string;
  prependText?: string;
}

export const StyledButton = ({
  variant = "light",
  icon,
  onClick,
  label,
  showLabel = true,
  disabled = false,
  loading = false,
  className = "",
  prependText = ""
}: ButtonProps) => {
  const iconOnly = !showLabel ? styles["icon-only"] + " icon-only":"";
  const isDisabled = disabled || loading ? styles["disabled"]: "";
  const isLoading = loading ? styles["loading"]: "";
  const mappedClassNames = className
    ?.split(" ")
    .filter(Boolean)
    .map((name) => styles[name] ?? name)
    .join(" ");
  return (
    <button
      className={
        styles["button"] 
        + " button "  
        + styles[variant] + " " 
        + variant + " "
        + iconOnly + " "
        + isDisabled + " "
        + isLoading + " "
        + mappedClassNames
        }
      onClick={onClick}
      disabled={disabled || loading}
      aria-busy={loading}
    >
      {loading ? (
        <span className={styles["spinner"]} aria-hidden="true"></span>
      ) : (
        icon //&& <AccessibleIcon.Root label={label}>{icon}</AccessibleIcon.Root>
      )}
      {prependText}
      {showLabel ? (
        <span>{loading ? "Loading..." : label}</span>
      ) : (
        <VisuallyHidden.Root>{loading ? "Loading..." : label}</VisuallyHidden.Root>
      )}
    </button>
  );
};
