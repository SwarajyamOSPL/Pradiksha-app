@script
<script>
    const razorpayScriptUrl = 'https://checkout.razorpay.com/v1/checkout.js'

    const loadRazorpay = () => new Promise((resolve, reject) => {
        if (window.Razorpay) {
            return resolve()
        }

        const script = document.createElement('script')
        script.src = razorpayScriptUrl
        script.onload = () => resolve()
        script.onerror = () => reject(new Error('Could not load Razorpay'))
        document.head.appendChild(script)
    })

    $wire.on('start-razorpay', async ({ options }) => {
        try {
            await loadRazorpay()
        } catch (error) {
            $wire.paymentDismissed()

            return
        }

        const checkout = new window.Razorpay({
            ...options,
            handler: (response) => $wire.confirmPayment(response),
            modal: { ondismiss: () => $wire.paymentDismissed() },
        })

        checkout.open()
    })
</script>
@endscript
