//go:build windows

package main

import (
	"encoding/xml"
	"fmt"
	"syscall"
	"unsafe"

	"golang.org/x/sys/windows"
)

var (
	modWevtapi       = windows.NewLazySystemDLL("wevtapi.dll")
	procEvtQuery     = modWevtapi.NewProc("EvtQuery")
	procEvtSubscribe = modWevtapi.NewProc("EvtSubscribe") // 🔴 ADICIONADO PARA O TEMPO REAL REAL
	procEvtNext      = modWevtapi.NewProc("EvtNext")
	procEvtRender    = modWevtapi.NewProc("EvtRender")

	modKernel32     = windows.NewLazySystemDLL("kernel32.dll")
	procCloseHandle = modKernel32.NewProc("CloseHandle")
)

// =========================
// MODELOS XML
// =========================

type Event struct {
	XMLName   xml.Name  `xml:"Event"`
	System    System    `xml:"System"`
	EventData EventData `xml:"EventData"`
}

type System struct {
	EventID int `xml:"EventID"`
}

type EventData struct {
	Data []DataAttr `xml:"Data"`
}

type DataAttr struct {
	Name  string `xml:"Name,attr"`
	Value string `xml:",chardata"`
}

// =========================
// EVTSUBSCRIBE (TEMPO REAL CORRIGIDO)
// =========================

func evtSubscribe(
	session windows.Handle,
	signalEvent windows.Handle,
	channelPath *uint16,
	query *uint16,
	bookmark windows.Handle,
	context uintptr,
	callback uintptr,
	flags uint32,
) (windows.Handle, error) {

	r0, _, err := procEvtSubscribe.Call(
		uintptr(session),
		uintptr(signalEvent),
		uintptr(unsafe.Pointer(channelPath)),
		uintptr(unsafe.Pointer(query)),
		uintptr(bookmark),
		context,
		callback,
		uintptr(flags),
	)

	// Se retornar 0, significa que a subscrição no Windows falhou
	if r0 == 0 {
		return 0, fmt.Errorf("EvtSubscribe falhou: %v", err)
	}

	return windows.Handle(r0), nil
}

// =========================
// EVTQUERY (HISTÓRICO)
// =========================

func evtQuery(channel *uint16, query *uint16) (windows.Handle, error) {
	r0, _, err := procEvtQuery.Call(
		0, // session local
		uintptr(unsafe.Pointer(channel)),
		uintptr(unsafe.Pointer(query)),
		uintptr(0), // flags = channel path
	)

	if r0 == 0 {
		return 0, fmt.Errorf("EvtQuery falhou: %v", err)
	}

	return windows.Handle(r0), nil
}

// =========================
// EVTNEXT (BATCH STREAM)
// =========================

func evtNext(queryHandle windows.Handle, batchSize uint32) ([]windows.Handle, error) {
	var returned uint32
	handles := make([]windows.Handle, batchSize)

	r0, _, err := procEvtNext.Call(
		uintptr(queryHandle),
		uintptr(batchSize),
		uintptr(unsafe.Pointer(&handles[0])),
		0, // timeout = infinito (streaming)
		0,
		uintptr(unsafe.Pointer(&returned)),
	)

	if r0 == 0 {
		return nil, fmt.Errorf("EvtNext falhou: %v", err)
	}

	return handles[:returned], nil
}

// =========================
// EVTRENDER (XML EXTRACTION)
// =========================

func getEventXML(eventHandle uintptr) string {
	var bufferUsed uint32
	var propertyCount uint32

	// 1ª chamada: descobrir tamanho do buffer necessário
	procEvtRender.Call(
		0,
		eventHandle,
		1, // EvtRenderEventXml
		0,
		0,
		uintptr(unsafe.Pointer(&bufferUsed)),
		uintptr(unsafe.Pointer(&propertyCount)),
	)

	if bufferUsed == 0 {
		return ""
	}

	buffer := make([]uint16, bufferUsed/2)

	r0, _, _ := procEvtRender.Call(
		0,
		eventHandle,
		1,
		uintptr(bufferUsed),
		uintptr(unsafe.Pointer(&buffer[0])),
		uintptr(unsafe.Pointer(&bufferUsed)),
		uintptr(unsafe.Pointer(&propertyCount)),
	)

	if r0 == 0 {
		return ""
	}

	return syscall.UTF16ToString(buffer)
}

// =========================
// SAFE CLOSE HANDLE
// =========================

func closeHandle(h windows.Handle) {
	if h != 0 {
		procCloseHandle.Call(uintptr(h))
	}
}

// Helper auxiliar para tratamento de erros
func winErr(res uintptr, err error) error {
	if res == 0 {
		if err != nil {
			return err
		}
		return fmt.Errorf("erro genérico win32")
	}
	return nil
}
