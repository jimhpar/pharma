export const getImageUrl = (path) => {
    if (!path || path === "null" || path === "undefined") {
        return "/images/no-image-available.png";
    }
    if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
    }
    const cleanPath = path.replace(/^(\/?public\/|\/)/, "");
    const baseUrl = (process.env.NEXT_PUBLIC_IMAGE_URL || "http://127.0.0.1:8001")
        .replace(/\/+$/, "")
        .replace(/\/storage$/, "");
    return `${baseUrl}/${cleanPath}`;
};