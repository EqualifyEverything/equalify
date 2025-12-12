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
}

export const StyledButton = ({
  variant = "light",
  icon,
  onClick,
  label,
  showLabel = true,
  disabled = false,
  loading = false,
  className = ""
}: ButtonProps) => {
  const iconOnly = !showLabel ? styles["icon-only"] + " icon-only":"";
  const isDisabled = disabled || loading ? styles["disabled"]: "";
  const isLoading = loading ? styles["loading"]: "";
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
        + className
        }
      onClick={onClick}
      disabled={disabled || loading}
      aria-busy={loading}
    >
      {loading ? (
        <span className={styles["spinner"]} aria-hidden="true"></span>
      ) : (
        icon && <AccessibleIcon.Root label={label}>{icon}</AccessibleIcon.Root>
      )}
      {showLabel ? (
        <span>{loading ? "Loading..." : label}</span>
      ) : (
        <VisuallyHidden.Root>{loading ? "Loading..." : label}</VisuallyHidden.Root>
      )}
    </button>
  );
};
