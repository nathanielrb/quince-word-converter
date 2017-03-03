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
            [saxon :as saxon]))

(use '[clojure.java.shell :only [sh]])

;   (sh "cp" file "./resources/data")

(defn tei2markdown [str]
  (let [xmldoc (saxon/compile-xml str)
        xsl (saxon/compile-xslt (slurp "./src/w2m/tei2markdown.xsl"))]
    (saxon/query "distinct-values(document)"
                 (xsl xmldoc))))

;;  (io/file file))
;; path (.getPath file)
        
(defn odt2tei [path]
  (let [xml-path (str path ".xml")]
    ;; (sh "rm" xml-path)
    (sh "php" "-f" "./src/w2m/algone-code/odt2tei/Odt.php" path)
    (slurp xml-path)))

(defn odt2markdown [file]
  (tei2markdown
   (odt2tei file)))

(defn upload-file [params]
  (let [file (get params "file")]
    (println (get params "path"))
    (println file)
    (println (.getPath (:tempfile file)))
    (response {:files (odt2markdown (.getPath (:tempfile file))) })))
;;        (assoc-in [:headers "Access-Control-Allow-Origin"]  "*")
;;        (assoc-in [:headers "Access-Control-Allow-Methods"] "GET,PUT,POST,DELETE,OPTIONS")
;;        (assoc-in [:headers "Access-Control-Allow-Headers"] "X-Requested-With,Content-Type,Cache-Control"))))

(defroutes app-routes
  ;; (GET "/" [] "Hello World")
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


  