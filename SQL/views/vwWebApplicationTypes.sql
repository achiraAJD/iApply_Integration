USE [LGS_UAT]
GO

/****** Object:  View [dbo].[vwWebApplicationTypes]    Script Date: 29/10/2021 12:34:43 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

ALTER View [dbo].[vwWebApplicationTypes]
as

	SELECT ApplicationTypes.* 
	FROM ApplicationTypes
	
;
GO


