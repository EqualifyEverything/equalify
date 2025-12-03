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
}

export const StyledButton = ({
  variant = "light",
  icon,
  onClick,
  label,
  showLabel = true,
}: ButtonProps) => {
  const iconOnly = !showLabel ? styles["icon-only"]:"";
  return (
    <button
      className={
        styles.button 
        + " button "  
        + styles[variant] + " " 
        + iconOnly 
        }
      onClick={onClick}
    >
      {icon && <AccessibleIcon.Root label={label}>{icon}</AccessibleIcon.Root>}
      {showLabel ? (
        <span>{label}</span>
      ) : (
        <VisuallyHidden.Root>{label}</VisuallyHidden.Root>
      )}
    </button>
  );
};
