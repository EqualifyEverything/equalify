import { FormEvent, ReactNode } from "react";
import * as Dialog from "@radix-ui/react-dialog";
import { StyledButton } from "./StyledButton";
// Same look as the Share dialog
import dialogStyles from "./ShareModal.module.scss";
import styles from "./ConfirmDialog.module.scss";

interface ConfirmDialogProps extends React.PropsWithChildren {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: ReactNode;
  confirmLabel: string;
  confirmVariant?: string;
  onConfirm: () => Promise<void> | void;
  loading?: boolean;
  confirmDisabled?: boolean;
  /** Where focus goes when the dialog closes; defaults to Radix's behavior */
  onCloseAutoFocus?: (e: Event) => void;
}

// A dialog with Cancel / Confirm buttons. Any children (e.g. a text input)
// render between the description and the buttons, inside a form, so Enter
// confirms.
export const ConfirmDialog = ({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel,
  confirmVariant = "dark",
  onConfirm,
  loading = false,
  confirmDisabled = false,
  onCloseAutoFocus,
  children,
}: ConfirmDialogProps) => {
  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    // React events bubble through portals, so without this a dialog opened
    // inside another form (e.g. the Audit Builder) would submit that form too
    e.stopPropagation();
    if (loading || confirmDisabled) return;
    await onConfirm();
  };

  return (
    <Dialog.Root open={open} onOpenChange={onOpenChange}>
      <Dialog.Portal>
        <Dialog.Overlay className={dialogStyles.overlay} />
        <Dialog.Content className={dialogStyles.content} onCloseAutoFocus={onCloseAutoFocus}>
          <Dialog.Title className={dialogStyles.title}>{title}</Dialog.Title>
          <Dialog.Description className={dialogStyles.description}>
            {description}
          </Dialog.Description>
          <form onSubmit={handleSubmit}>
            {children}
            <div className={styles.actions}>
              <Dialog.Close asChild>
                <StyledButton label="Cancel" variant="light" onClick={undefined} />
              </Dialog.Close>
              <StyledButton
                type="submit"
                label={confirmLabel}
                variant={confirmVariant}
                onClick={undefined}
                loading={loading}
                disabled={loading || confirmDisabled}
              />
            </div>
          </form>
          <Dialog.Close asChild>
            <button className={dialogStyles["close-button"]} aria-label="Close dialog">
              ×
            </button>
          </Dialog.Close>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
};
