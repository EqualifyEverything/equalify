import * as Dialog from '@radix-ui/react-dialog';

export const Modal = ({ open, setOpen, children, className = '' }) => (
    <Dialog.Root open={open} onOpenChange={(value) => { setOpen(value) }}>
        <Dialog.Portal>
            <Dialog.Overlay className="bg-[#0009] data-[state=open]:animate-overlayShow fixed inset-0 z-[2]" />
            <Dialog.Content className={`data-[state=open]:animate-contentShow fixed top-[40%] left-[50%] max-h-[85vh] w-[90vw] max-w-[770px] translate-x-[-50%] translate-y-[-50%] rounded-[6px] bg-background p-[10px] sm:p-[25px] shadow-[hsl(206_22%_7%_/_35%)_0px_10px_38px_-10px,_hsl(206_22%_7%_/_20%)_0px_10px_20px_-15px] focus:outline-none ${className} z-40 border-[1px] border-border`}>
                <Dialog.Description className="text-text mt-1">
                    {children}
                </Dialog.Description>
                <Dialog.Close asChild>
                    <button
                        className="text-text absolute top-2 right-2 bg-transparent border-none inline-flex h-6 w-6 items-center justify-center hover:opacity-50"
                        aria-label="Close"
                    >
                        {`×`}
                    </button>
                </Dialog.Close>
            </Dialog.Content>
        </Dialog.Portal>
    </Dialog.Root>
);