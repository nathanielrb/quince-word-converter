(ns w2m.handler
  (:require [compojure.core :refer :all]
            [compojure.route :as route]
            [ring.middleware.defaults :refer [wrap-defaults api-defaults]]
            [ring.middleware [multipart-params :as mp]]
            [ring.middleware.params]
            [ring.middleware.multipart-params :as mp]
            [ring.middleware.json :refer [wrap-json-response]]
            [ring.middleware.cors :refer [wrap-cors]]
            [ring.util.response :refer [response]]
            [clojure.java.io :as io]
            [clojure.string :as str]
            [clojure.xml :as xml]
            [clojure.zip :as zip]
            [clojure.data.codec.base64 :as b64]
            [saxon :as saxon]))

(use '[clojure.java.shell :only [sh]])

(defn slurp-bytes
  "Slurp the bytes from a slurpable thing"
  [x]
  (with-open [out (java.io.ByteArrayOutputStream.)]
    (clojure.java.io/copy (clojure.java.io/input-stream x) out)
    (.toByteArray out)))

(defn bytes-to-base64-string [original]
  (String. (b64/encode original) "UTF-8"))

(defn string-to-base64-string [original]
  (bytes-to-base64-string
   (.getBytes original)))

(defn slurp-base64-string [path]
  (bytes-to-base64-string
   (slurp-bytes path)))

(defn serialize-to-string [node]
  (if (string? node)
    node
    (let [baos (java.io.ByteArrayOutputStream.)]
      (saxon/serialize node baos)
      (String. (.toByteArray baos)))))

(defn tei2markdown [str]
  (let [xmldoc (saxon/compile-xml str)
        xsl (saxon/compile-xslt (slurp "./src/w2m/tei2markdown.xsl"))]
    (map (fn [doc]
           {:title (saxon/query "distinct-values(title)" doc)
            :body  (string-to-base64-string
                    ;(serialize-to-string
                     (saxon/query "distinct-values(body)" doc))})
         (saxon/query "documents/document" (xsl xmldoc)))))

(defn get-images [dir]
  (map (fn [img] {:filename img
                  :body (slurp-base64-string (str dir "/" img))})
       (.list (io/file dir))))

(defn odt2tei [path]
  (let [bname (str/replace path #"\.odt$" "")
        xml-path (str bname ".xml")
        images-path (str bname"-img")]
    (sh "php" "-f" "./src/w2m/algone-code/odt2tei/Odt.php" path)

    {:images (get-images images-path)
     :xml (slurp xml-path)}))

(defn odt2markdown [path]
  (let [tei (odt2tei path)]
    (concat (tei2markdown (:xml tei))
            (:images tei))))

(defn convert-file [file]
  (odt2markdown (.getPath (:tempfile file))))

(defn upload-file [params]
  (response {:files (convert-file (get params "file"))}))

(defn test-odt []
  (odt2markdown "test.odt"))

(defroutes app-routes
  (mp/wrap-multipart-params
   (wrap-json-response
    (POST "/upload" {params :params}
          (upload-file params))))

  (route/not-found "Not Found"))

(def cors-headers
  "Generic CORS headers"
  {"Access-Control-Allow-Origin"  "http://localhost:8080"
   "Access-Control-Allow-Headers" "Content-Type,*"
   "Access-Control-Allow-Methods" "GET,POST,OPTIONS"})

(defn preflight?
  "Returns true if the request is a preflight request"
  [request]
  (= (request :request-method) :options))

(defn all-cors
  "Allow requests from all origins - also check preflight"
  [handler]
  (fn [request]
    (if (preflight? request)
      {:status 200
       :headers cors-headers
       :body "preflight complete"}
      (let [response (handler request)]
        (update-in response [:headers]
                   merge cors-headers )))))
(def app
  (-> app-routes
      (wrap-defaults api-defaults)
      (all-cors)))
;      (wrap-cors :access-control-allow-origin [#"http://localhost:8080"]
 ;                :access-control-allow-methods [:post :get]
  ;               :access-control-allow-headers ["Content-Type"])))


  

