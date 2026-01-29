    function printContent() {
        var printWindow = window.open('', '_blank');
        var editorContent = document.getElementById('editor').innerHTML;

        printWindow.document.write('<html><head><title>Print Content</title></head><body>');
        printWindow.document.write(editorContent);
        printWindow.document.write('</body></html>');

        printWindow.document.close();
        printWindow.print();
    }
