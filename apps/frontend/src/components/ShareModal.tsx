import { useState } from "react";
import * as Dialog from "@radix-ui/react-dialog";
import { FaClipboard, FaCheckCircle } from "react-icons/fa";
import { StyledButton } from "./StyledButton";
import styles from "./ShareModal.module.scss";

interface ShareModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  shareUrl: string;
}

export const ShareModal = ({ open, onOpenChange, shareUrl }: ShareModalProps) => {
  const [copied, setCopied] = useState(false);

  const copyToClipboard = async () => {
    try {
      await navigator.clipboard.writeText(shareUrl);
      setCopied(true);
      setTimeout(() => setCopied(false), 2500);
    } catch (err) {
      console.error("Failed to copy share URL:", err);
    }
  };

  return (
    <Dialog.Root open={open} onOpenChange={onOpenChange}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.content}>
          <Dialog.Title className={styles.title}>Share Report</Dialog.Title>
          <Dialog.Description className={styles.description}>
            Anyone with this link can view this accessibility report — no login required.
          </Dialog.Description>

          <div className={styles["url-row"]}>
            <label htmlFor="share-modal-url" className="sr-only">
              Shareable link
            </label>
            <input
              id="share-modal-url"
              type="text"
              readOnly
              value={shareUrl}
              onFocus={(e) => e.target.select()}
              onClick={(e) => (e.target as HTMLInputElement).select()}
            />
            <StyledButton
              onClick={copyToClipboard}
              label={copied ? "Copied!" : "Copy link"}
              icon={copied ? <FaCheckCircle /> : <FaClipboard />}
              variant={copied ? "green" : "dark"}
            />
          </div>

          {/* Announced to screen readers when copy succeeds */}
          <div role="status" aria-live="polite" aria-atomic="true" className="sr-only">
            {copied ? "Link copied to clipboard." : ""}
          </div>

          <Dialog.Close asChild>
            <button className={styles["close-button"]} aria-label="Close share dialog">
              ×
            </button>
          </Dialog.Close>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
};
